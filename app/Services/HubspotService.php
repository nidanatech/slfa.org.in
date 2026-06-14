<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\HubspotConnection;
use Carbon\Carbon;

class HubspotService
{
    protected $clientId;
    protected $clientSecret;
    protected $redirectUri;
    protected $baseUrl = 'https://api.hubapi.com';

    public function __construct()
    {
        $this->clientId = config('services.hubspot.client_id');
        $this->clientSecret = config('services.hubspot.client_secret');
        $this->redirectUri = config('services.hubspot.redirect_uri');
    }

    /**
     * Get the URL to redirect the user to HubSpot for authorization.
     */
    public function getAuthUrl()
    {
        $scopes = [
            'crm.objects.contacts.read',
            'crm.objects.contacts.write',
            'crm.objects.deals.read',
            'crm.objects.deals.write',
            'crm.objects.owners.read',
        ];

        $query = http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => implode(' ', $scopes),
        ]);

        return "https://app.hubspot.com/oauth/authorize?" . $query;
    }

    /**
     * Exchange an authorization code for an access token.
     */
    public function exchangeCode($code)
    {
        $response = Http::asForm()->post("{$this->baseUrl}/oauth/v1/token", [
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'code' => $code,
        ]);

        if (!$response->successful()) {
            Log::error("HubspotService: Failed to exchange code: " . $response->body());
            return null;
        }

        return $response->json();
    }

    /**
     * Refresh an existing access token.
     */
    public function refreshToken(HubspotConnection $connection)
    {
        $response = Http::asForm()->post("{$this->baseUrl}/oauth/v1/token", [
            'grant_type' => 'refresh_token',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $connection->refresh_token,
        ]);

        if (!$response->successful()) {
            Log::error("HubspotService: Failed to refresh token: " . $response->body());
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
    public function getValidToken(HubspotConnection $connection)
    {
        if ($connection->expires_at && $connection->expires_at->isFuture()) {
            return $connection->access_token;
        }

        return $this->refreshToken($connection);
    }

    /**
     * Make an authenticated request to the HubSpot API.
     */
    protected function request(HubspotConnection $connection, $method, $endpoint, $options = [])
    {
        $token = $this->getValidToken($connection);
        if (!$token) return null;

        $response = Http::withToken($token)->$method("{$this->baseUrl}{$endpoint}", $options);

        if (!$response->successful()) {
            Log::error("HubspotService API Error: {$method} {$endpoint} - " . $response->body());
        }

        return $response;
    }

    /**
     * Synchronize pipelines and stages from HubSpot.
     */
    public function syncPipelines(HubspotConnection $connection)
    {
        $response = $this->request($connection, 'get', '/crm/v3/pipelines/deals');
        if (!$response || !$response->successful()) return false;

        $pipelines = $response->json()['results'] ?? [];
        $tenantId = $connection->tenant_id;

        foreach ($pipelines as $hsPipeline) {
            $pipeline = \App\Models\Pipeline::updateOrCreate(
                ['tenant_id' => $tenantId, 'external_id' => 'hubspot_' . $hsPipeline['id']],
                [
                    'name' => $hsPipeline['label'],
                    'is_active' => !$hsPipeline['archived'],
                ]
            );

            // Sync Stages
            foreach ($hsPipeline['stages'] as $hsStage) {
                \App\Models\PipelineStage::updateOrCreate(
                    ['pipeline_id' => $pipeline->id, 'external_id' => 'hubspot_' . $hsStage['id']],
                    [
                        'name' => $hsStage['label'],
                        'order_index' => $hsStage['displayOrder'],
                        'probability' => (int) (($hsStage['metadata']['probability'] ?? 0) * 100),
                        'is_closed' => ($hsStage['metadata']['isClosed'] ?? 'false') === 'true',
                    ]
                );
            }
        }

        return true;
    }

    /**
     * Synchronize dynamic custom fields from HubSpot.
     */
    public function syncCustomFields(HubspotConnection $connection, $objectType)
    {
        $endpoint = $objectType === 'contact' ? '/crm/v3/properties/contacts' : '/crm/v3/properties/deals';
        $response = $this->request($connection, 'get', $endpoint);
        if (!$response || !$response->successful()) return false;

        $properties = $response->json()['results'] ?? [];
        $tenantId = $connection->tenant_id;

        // Ensure object exists
        $object = \App\Models\CustomFieldObject::firstOrCreate(['name' => $objectType]);

        // Known fields to skip (mapped to native columns)
        $skipFields = [
            'firstname', 'lastname', 'email', 'phone', 'dealname', 'amount', 'dealstage', 'pipeline',
            'createdate', 'lastmodifieddate', 'hs_object_id'
        ];

        foreach ($properties as $prop) {
            if (in_array($prop['name'], $skipFields) || $prop['archived']) continue;

            $fieldType = $this->mapHubspotTypeToSydkic($prop['type']);
            
            \App\Models\CustomField::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'object_id' => $object->id,
                    'key' => 'hubspot_' . $prop['name'],
                ],
                [
                    'name' => $prop['label'],
                    'field_type' => $fieldType,
                    'ui_type' => $prop['fieldType'] ?? 'text',
                    'options_json' => $prop['options'] ?? null,
                ]
            );
        }

        return true;
    }

    protected function mapHubspotTypeToSydkic($type)
    {
        return match ($type) {
            'number' => 'number',
            'date', 'datetime' => 'date',
            'bool', 'enumeration' => 'boolean',
            default => 'text',
        };
    }

    /**
     * Update custom field values for a record.
     */
    public function updateCustomFieldValues(HubspotConnection $connection, $recordId, $objectType, $properties)
    {
        $tenantId = $connection->tenant_id;
        $object = \App\Models\CustomFieldObject::where('name', $objectType)->first();
        if (!$object) return;

        foreach ($properties as $key => $value) {
            if (is_null($value) || $value === '') continue;

            $field = \App\Models\CustomField::where('tenant_id', $tenantId)
                ->where('object_id', $object->id)
                ->where('key', 'hubspot_' . $key)
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
     * Synchronize contacts from HubSpot to Sydkic.
     */
    public function syncContacts(HubspotConnection $connection)
    {
        // First, sync custom fields metadata
        $this->syncCustomFields($connection, 'contact');

        $tenantId = $connection->tenant_id;
        $object = \App\Models\CustomFieldObject::where('name', 'contact')->first();

        // Fetch all properties to include custom ones
        $customFields = \App\Models\CustomField::where('tenant_id', $tenantId)
            ->where('object_id', $object->id)
            ->where('key', 'like', 'hubspot_%')
            ->get();

        $allHSProps = array_merge(['firstname', 'lastname', 'email', 'phone'], $customFields->pluck('key')->map(fn($k) => str_replace('hubspot_', '', $k))->toArray());

        $response = $this->request($connection, 'get', '/crm/v3/objects/contacts', [
            'properties' => implode(',', array_unique($allHSProps)),
            'limit' => 100,
        ]);

        if (!$response || !$response->successful()) return false;

        $contacts = $response->json()['results'] ?? [];

        foreach ($contacts as $hsContact) {
            $props = $hsContact['properties'];
            $email = $props['email'] ?? null;
            if (!$email) continue;

            $contact = \App\Models\Contact::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'external_id' => 'hubspot_' . $hsContact['id'],
                ],
                [
                    'first_name' => $props['firstname'] ?? '',
                    'last_name' => $props['lastname'] ?? '',
                    'name' => trim(($props['firstname'] ?? '') . ' ' . ($props['lastname'] ?? '')),
                    'emails' => [['value' => $email, 'type' => 'work']],
                    'phone' => $props['phone'] ?? null,
                ]
            );

            $this->updateCustomFieldValues($connection, $contact->id, 'contact', $props);
        }

        return true;
    }

    /**
     * Synchronize deals from HubSpot to Sydkic Opportunities.
     */
    public function syncDeals(HubspotConnection $connection)
    {
        // First, sync pipelines and stages
        $this->syncPipelines($connection);
        
        // Then, sync custom fields metadata
        $this->syncCustomFields($connection, 'opportunity');

        $tenantId = $connection->tenant_id;
        $object = \App\Models\CustomFieldObject::where('name', 'opportunity')->first();

        $customFields = \App\Models\CustomField::where('tenant_id', $tenantId)
            ->where('object_id', $object->id)
            ->where('key', 'like', 'hubspot_%')
            ->get();

        $allHSProps = array_merge(['dealname', 'amount', 'dealstage', 'pipeline'], $customFields->pluck('key')->map(fn($k) => str_replace('hubspot_', '', $k))->toArray());

        $response = $this->request($connection, 'get', '/crm/v3/objects/deals', [
            'properties' => implode(',', array_unique($allHSProps)),
            'limit' => 100,
        ]);

        if (!$response || !$response->successful()) return false;

        $deals = $response->json()['results'] ?? [];

        foreach ($deals as $hsDeal) {
            $props = $hsDeal['properties'];
            
            $pipeline = \App\Models\Pipeline::where('tenant_id', $tenantId)
                ->where('external_id', 'hubspot_' . ($props['pipeline'] ?? ''))
                ->first();

            if (!$pipeline) {
                $pipeline = \App\Models\Pipeline::where('tenant_id', $tenantId)->first();
            }
            if (!$pipeline) continue;

            $stage = \App\Models\PipelineStage::where('pipeline_id', $pipeline->id)
                ->where('external_id', 'hubspot_' . ($props['dealstage'] ?? ''))
                ->first();

            if (!$stage) {
                $stage = $pipeline->stages()->orderBy('order_index')->first();
            }
            if (!$stage) continue;

            $opportunity = \App\Models\Opportunity::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'external_id' => 'hubspot_' . $hsDeal['id'],
                ],
                [
                    'name' => $props['dealname'] ?? 'HubSpot Deal',
                    'pipeline_id' => $pipeline->id,
                    'stage_id' => $stage->id,
                    'value_amount' => $props['amount'] ?? 0,
                    'status' => 'open',
                ]
            );

            $this->updateCustomFieldValues($connection, $opportunity->id, 'opportunity', $props);
        }

        return true;
    }
}
