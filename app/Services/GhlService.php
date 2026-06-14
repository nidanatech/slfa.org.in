<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\GhlConnection;
use Carbon\Carbon;

class GhlService
{
    protected $clientId;
    protected $clientSecret;
    protected $redirectUri;
    protected $baseUrl = 'https://services.leadconnectorhq.com';

    public function __construct()
    {
        $this->clientId = config('services.ghl.client_id');
        $this->clientSecret = config('services.ghl.client_secret');
        $this->redirectUri = config('services.ghl.redirect_uri');
    }

    /**
     * Get the URL to redirect the user to GHL for authorization.
     */
    public function getAuthUrl()
    {
        $query = http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'contacts.readonly contacts.write opportunities.readonly opportunities.write pipelines.readonly',
        ]);

        return "https://marketplace.gohighlevel.com/oauth/chooselocation?" . $query;
    }

    /**
     * Exchange an authorization code for an access token.
     */
    public function exchangeCode($code)
    {
        $response = Http::asForm()->post("{$this->baseUrl}/oauth/token", [
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'code' => $code,
        ]);

        if (!$response->successful()) {
            Log::error("GhlService: Failed to exchange code: " . $response->body());
            return null;
        }

        return $response->json();
    }

    /**
     * Refresh an existing access token.
     */
    public function refreshToken(GhlConnection $connection)
    {
        $response = Http::asForm()->post("{$this->baseUrl}/oauth/token", [
            'grant_type' => 'refresh_token',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $connection->refresh_token,
        ]);

        if (!$response->successful()) {
            Log::error("GhlService: Failed to refresh token: " . $response->body());
            return null;
        }

        $data = $response->json();

        $connection->update([
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'],
            'expires_at' => Carbon::now()->addSeconds($data['expires_in']),
        ]);

        return $data['access_token'];
    }

    /**
     * Get a valid access token, refreshing if necessary.
     */
    public function getValidToken(GhlConnection $connection)
    {
        if ($connection->expires_at && $connection->expires_at->isFuture()) {
            return $connection->access_token;
        }

        return $this->refreshToken($connection);
    }

    /**
     * Make an authenticated request to the GHL API.
     */
    protected function request(GhlConnection $connection, $method, $endpoint, $options = [])
    {
        $token = $this->getValidToken($connection);
        if (!$token) return null;

        $response = Http::withToken($token)
            ->withHeaders(['Version' => '2021-07-28'])
            ->$method("{$this->baseUrl}{$endpoint}", $options);

        if (!$response->successful()) {
            Log::error("GhlService API Error: {$method} {$endpoint} - " . $response->body());
        }

        return $response;
    }

    /**
     * Synchronize pipelines and stages from GHL.
     */
    public function syncPipelines(GhlConnection $connection)
    {
        $response = $this->request($connection, 'get', "/pipelines/", [
            'locationId' => $connection->location_id
        ]);
        
        if (!$response || !$response->successful()) return false;

        $pipelines = $response->json()['pipelines'] ?? [];
        $tenantId = $connection->tenant_id;

        foreach ($pipelines as $ghlPipeline) {
            $pipeline = \App\Models\Pipeline::updateOrCreate(
                ['tenant_id' => $tenantId, 'external_id' => 'ghl_' . $ghlPipeline['id']],
                [
                    'name' => $ghlPipeline['name'],
                    'is_active' => true,
                ]
            );

            // Sync Stages
            foreach ($ghlPipeline['stages'] as $index => $ghlStage) {
                \App\Models\PipelineStage::updateOrCreate(
                    ['pipeline_id' => $pipeline->id, 'external_id' => 'ghl_' . $ghlStage['id']],
                    [
                        'name' => $ghlStage['name'],
                        'order_index' => $index,
                    ]
                );
            }
        }

        return true;
    }

    /**
     * Synchronize dynamic custom fields from GHL.
     */
    public function syncCustomFields(GhlConnection $connection)
    {
        $response = $this->request($connection, 'get', "/custom-fields/", [
            'locationId' => $connection->location_id,
            'model' => 'contact'
        ]);

        if (!$response || !$response->successful()) return false;

        $fields = $response->json()['customFields'] ?? [];
        $tenantId = $connection->tenant_id;
        $object = \App\Models\CustomFieldObject::firstOrCreate(['name' => 'contact']);

        foreach ($fields as $field) {
            $fieldType = $this->mapGhlTypeToSydkic($field['dataType'] ?? 'TEXT');
            
            \App\Models\CustomField::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'object_id' => $object->id,
                    'key' => 'ghl_' . $field['id'],
                ],
                [
                    'name' => $field['name'],
                    'field_type' => $fieldType,
                    'ui_type' => $field['dataType'] ?? 'text',
                    'options_json' => $field['options'] ?? null,
                ]
            );
        }

        return true;
    }

    protected function mapGhlTypeToSydkic($type)
    {
        return match (strtoupper($type)) {
            'NUMERICAL' => 'number',
            'DATE' => 'date',
            'CHECKBOX' => 'boolean',
            default => 'text',
        };
    }

    /**
     * Update custom field values for a record.
     */
    public function updateCustomFieldValues(GhlConnection $connection, $recordId, $objectType, $customFieldValues)
    {
        $tenantId = $connection->tenant_id;
        $object = \App\Models\CustomFieldObject::where('name', $objectType)->first();
        if (!$object) return;

        foreach ($customFieldValues as $cf) {
            $fieldId = $cf['id'] ?? null;
            $value = $cf['value'] ?? null;
            if (!$fieldId || is_null($value)) continue;

            $field = \App\Models\CustomField::where('tenant_id', $tenantId)
                ->where('object_id', $object->id)
                ->where('key', 'ghl_' . $fieldId)
                ->first();

            if (!$field) continue;

            $data = [
                'object_id' => $object->id,
                'record_id' => $recordId,
                'field_id' => $field->id,
            ];

            $valueKey = match ($field->field_type) {
                'number' => 'value_number',
                'date' => 'value_date',
                'boolean' => 'value_boolean',
                default => 'value_text',
            };

            \App\Models\CustomFieldValue::updateOrCreate($data, [$valueKey => $value]);
        }
    }

    /**
     * Synchronize contacts from GHL to Sydkic.
     */
    public function syncContacts(GhlConnection $connection)
    {
        // First sync custom fields metadata
        $this->syncCustomFields($connection);

        $response = $this->request($connection, 'get', "/contacts/", [
            'locationId' => $connection->location_id,
            'limit' => 100,
        ]);

        if (!$response || !$response->successful()) return false;

        $contacts = $response->json()['contacts'] ?? [];
        $tenantId = $connection->tenant_id;

        foreach ($contacts as $ghlContact) {
            $email = $ghlContact['email'] ?? null;
            if (!$email) continue;

            $contact = \App\Models\Contact::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'external_id' => 'ghl_' . $ghlContact['id'],
                ],
                [
                    'first_name' => $ghlContact['firstName'] ?? '',
                    'last_name' => $ghlContact['lastName'] ?? '',
                    'name' => trim(($ghlContact['firstName'] ?? '') . ' ' . ($ghlContact['lastName'] ?? '')),
                    'emails' => [['value' => $email, 'type' => 'work']],
                    'phone' => $ghlContact['phone'] ?? null,
                ]
            );

            if (!empty($ghlContact['customFields'])) {
                $this->updateCustomFieldValues($connection, $contact->id, 'contact', $ghlContact['customFields']);
            }
        }

        return true;
    }

    /**
     * Synchronize opportunities from GHL to Sydkic.
     */
    public function syncOpportunities(GhlConnection $connection)
    {
        $response = $this->request($connection, 'get', "/opportunities/", [
            'locationId' => $connection->location_id,
            'limit' => 100,
        ]);

        if (!$response || !$response->successful()) return false;

        $opportunities = $response->json()['opportunities'] ?? [];
        $tenantId = $connection->tenant_id;

        foreach ($opportunities as $ghlOpp) {
            $pipeline = \App\Models\Pipeline::where('tenant_id', $tenantId)
                ->where('external_id', 'ghl_' . ($ghlOpp['pipelineId'] ?? ''))
                ->first();

            if (!$pipeline) {
                $pipeline = \App\Models\Pipeline::where('tenant_id', $tenantId)->first();
            }
            if (!$pipeline) continue;

            $stage = \App\Models\PipelineStage::where('pipeline_id', $pipeline->id)
                ->where('external_id', 'ghl_' . ($ghlOpp['pipelineStageId'] ?? ''))
                ->first();

            if (!$stage) {
                $stage = $pipeline->stages()->orderBy('order_index')->first();
            }
            if (!$stage) continue;

            \App\Models\Opportunity::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'external_id' => 'ghl_' . $ghlOpp['id'],
                ],
                [
                    'name' => $ghlOpp['name'] ?? 'GHL Opportunity',
                    'pipeline_id' => $pipeline->id,
                    'stage_id' => $stage->id,
                    'value_amount' => $ghlOpp['monetaryValue'] ?? 0,
                    'status' => 'open',
                ]
            );
        }

        return true;
    }
}
