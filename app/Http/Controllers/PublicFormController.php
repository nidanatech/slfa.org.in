<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\PipelineStage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class PublicFormController extends Controller
{
    /**
     * Display the public form
     */
    public function show($businessSlug, $formSlug = null)
    {
        // For backward compatibility, check if $businessSlug is actually a form identifier
        // and $formSlug is null.
        if ($formSlug === null) {
            $identifier = $businessSlug;
            $channel = Channel::where('type', 'lead_form')
                ->where('id', $identifier)
                ->first();

            if (!$channel) {
                $channel = Channel::where('type', 'lead_form')
                    ->whereJsonContains('config->slug', $identifier)
                    ->firstOrFail();
            }
        } else {
            // New pattern: /{businessSlug}/{formSlug}
            $businessProfile = \App\Models\BusinessProfile::where('slug', $businessSlug)->firstOrFail();
            
            $channel = Channel::where('tenant_id', $businessProfile->tenant_id)
                ->where('type', 'lead_form')
                ->whereJsonContains('config->slug', $formSlug)
                ->firstOrFail();
        }

        // Get business profile for logo
        $businessProfile = \App\Models\BusinessProfile::where('tenant_id', $channel->tenant_id)->first();
        
        // Use business profile logo if form branding doesn't have one
        $branding = $channel->config['branding'] ?? [];
        if (empty($branding['logo_url']) && $businessProfile && $businessProfile->logo_url) {
            $branding['logo_url'] = $businessProfile->logo_url;
        }

        return Inertia::render('Forms/PublicShow', [
            'form' => $channel,
            'branding' => $branding,
            'fields' => $channel->config['fields'] ?? [],
            'title' => $channel->config['title'],
            'description' => $channel->config['description'],
            'businessSlug' => $formSlug ? $businessSlug : null,
            'formSlug' => $formSlug ?: $businessSlug,
        ]);
    }

    /**
     * Handle form submission
     */
    public function submit(Request $request, $businessSlug, $formSlug = null)
    {
        if ($formSlug === null) {
            $identifier = $businessSlug;
            $channel = Channel::where('type', 'lead_form')
                 ->where('id', $identifier)
                 ->orWhereJsonContains('config->slug', $identifier)
                 ->firstOrFail();
        } else {
            $businessProfile = \App\Models\BusinessProfile::where('slug', $businessSlug)->firstOrFail();
            
            $channel = Channel::where('tenant_id', $businessProfile->tenant_id)
                ->where('type', 'lead_form')
                ->whereJsonContains('config->slug', $formSlug)
                ->firstOrFail();
            
            $identifier = $formSlug; // For redirect back
        }

        // 1. Validate Fields
        // We need to dynamically construct validation rules based on config
        $rules = [];
        $fields = $channel->config['fields'] ?? [];
        
        foreach ($fields as $field) {
            $fieldKey = $field['key'];
            
            // Initialize rule if not exists
            if (!isset($rules[$fieldKey])) {
                $rules[$fieldKey] = '';
            }
            
            if (!empty($field['required'])) {
                $rules[$fieldKey] = 'required';
            }
            
            // Add email validation for email fields
            if ($fieldKey === 'email') {
                $rules[$fieldKey] = $rules[$fieldKey] ? $rules[$fieldKey] . '|email' : 'email';
            }
            
            // Add URL validation for website fields
            if ($fieldKey === 'website') {
                $rules[$fieldKey] = $rules[$fieldKey] ? $rules[$fieldKey] . '|url' : 'nullable|url';
            }
            
            // Remove empty rules
            if (empty($rules[$fieldKey])) {
                unset($rules[$fieldKey]);
            }
        }
        
        // Custom Validation Logic: Ensure at least Email OR Phone is present?
        // User request: "The lead capture always should have First name, last name and phone or email"
        // If we enforce this in Builder, we can enforce strict validation here.
        // For now, let's rely on the rules built from the form config.

        $validated = $request->validate($rules);

        // 2. Create/Update Contact
        $email = $validated['email'] ?? null;
        $phone = $validated['phone'] ?? null;
        $firstName = $validated['first_name'] ?? '';
        $lastName = $validated['last_name'] ?? '';
        $name = trim("$firstName $lastName");

        // Ensure we have at least first name or last name
        if (empty($name)) {
            return back()->withErrors(['first_name' => 'Please provide at least a first name or last name.'])->withInput();
        }

        // Strategy: Match by Email, then Phone
        $contact = null;
        
        if ($email) {
            // Try to find by email in JSON array
            $contact = Contact::where('tenant_id', $channel->tenant_id)
                ->where(function($query) use ($email) {
                    $query->whereJsonContains('emails', [['value' => $email]]);
                })
                ->first();
                
            // If not found, try a broader search
            if (!$contact) {
                $contact = Contact::where('tenant_id', $channel->tenant_id)
                    ->where('emails', 'like', '%' . $email . '%')
                    ->first();
            }
        }
        
        if (!$contact && $phone) {
            // Try to find by phone in JSON array
            $contact = Contact::where('tenant_id', $channel->tenant_id)
                ->where(function($query) use ($phone) {
                    $query->whereJsonContains('phones', [['value' => $phone]]);
                })
                ->first();
                
            // If not found, try a broader search
            if (!$contact) {
                $contact = Contact::where('tenant_id', $channel->tenant_id)
                    ->where('phones', 'like', '%' . $phone . '%')
                    ->first();
            }
        }

        // Generate external_id for new contacts (required field)
        $externalId = $contact ? $contact->external_id : 'form_' . uniqid();

        $contactData = [
            'tenant_id' => $channel->tenant_id,
            'channel_id' => $channel->id, // Source
            'external_id' => $externalId,
            'name' => $name,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'meta' => ['source' => 'lead_form', 'form_id' => $channel->id],
        ];

        // Prepare Emails/Phones array
        if ($email) $contactData['emails'] = [['type' => 'work', 'value' => $email]];
        if ($phone) $contactData['phones'] = [['type' => 'work', 'value' => $phone]];

        // Handle additional standard fields
        $additionalFields = ['company', 'website', 'address', 'city', 'state', 'zip', 'country', 'message'];
        foreach ($additionalFields as $field) {
            if (isset($validated[$field]) && !empty($validated[$field])) {
                // Store in meta for now, or map to specific columns if they exist
                $contactData['meta'][$field] = $validated[$field];
            }
        }

        // Handle Custom Fields (Iterate submitted data that matches custom_*)
        // We'll do this after creation.

        try {
            if ($contact) {
                $contact->update(array_filter($contactData, fn($value) => $value !== null));
            } else {
                $contact = Contact::create($contactData);
            }
        } catch (\Exception $e) {
            Log::error('Form submission contact creation failed', [
                'error' => $e->getMessage(),
                'data' => $contactData
            ]);
            return back()->withErrors(['form' => 'Unable to save your information. Please try again or contact support.'])->withInput();
        }

        // 3. Save Custom Fields
        foreach ($request->all() as $key => $value) {
            if (str_starts_with($key, 'custom_')) {
                // Key format: custom_{id}
                $fieldId = substr($key, 7);
                // Validate field exists?
                \App\Models\CustomFieldValue::updateOrCreate(
                    [
                        'custom_field_id' => $fieldId,
                        'record_type' => Contact::class,
                        'record_id' => $contact->id,
                    ],
                    ['value' => $value]
                );
            }
        }

        // 4. Create Opportunity (Optional)
        $actions = $channel->config['actions'] ?? [];
        $opportunity = null;

        if (!empty($actions['create_opportunity']) && $actions['create_opportunity']) {
            $pipelineId = $actions['pipeline_id'] ?? null;
            $stageId = $actions['stage_id'] ?? null;

            if ($pipelineId && $stageId) {
                // Check for product association
                $productId = $actions['product_id'] ?? null;
                $variationId = $actions['product_variation_id'] ?? null;
                
                $requiresPayment = false;
                $stripePriceId = null;

                $tenant = $channel->tenant;
                if ($tenant->stripeConfig && $tenant->stripeConfig->is_active) {
                    if ($variationId) {
                        $variation = \App\Models\ProductVariation::find($variationId);
                        if ($variation && $variation->price > 0) {
                            $requiresPayment = true;
                            $stripePriceId = $variation->stripe_price_id;
                        }
                    } elseif ($productId) {
                        $product = \App\Models\Product::find($productId);
                        if ($product && $product->price > 0) {
                            $requiresPayment = true;
                            $stripePriceId = $product->stripe_price_id;
                        }
                    }
                }

                $opportunity = Opportunity::create([
                    'tenant_id' => $channel->tenant_id,
                    'pipeline_id' => $pipelineId,
                    'stage_id' => $stageId,
                    'contact_id' => $contact->id,
                    'name' => "New Lead: " . $name,
                    'status' => 'open',
                    'payment_status' => $requiresPayment ? 'pending' : 'n/a',
                    'product_id' => $productId,
                    'product_variation_id' => $variationId,
                ]);

                if ($requiresPayment && $stripePriceId) {
                    $stripeService = app(\App\Services\TenantStripeService::class);
                    $session = $stripeService->createCheckoutSession(
                        $tenant,
                        [['price' => $stripePriceId, 'quantity' => 1]],
                        route('forms.public.show', [$businessSlug, $formSlug ?: $businessSlug]) . '?status=success&session_id={CHECKOUT_SESSION_ID}',
                        route('forms.public.show', [$businessSlug, $formSlug ?: $businessSlug]) . '?status=cancelled',
                        [
                            'opportunity_id' => $opportunity->id,
                            'contact_id' => $contact->id,
                            'type' => 'form_submission'
                        ]
                    );

                    $opportunity->update(['stripe_session_id' => $session->id]);
                    return Inertia::location($session->url);
                }
            }
        }

        // Return success - redirect to same form with success indicator
        if ($formSlug) {
            return redirect()->route('forms.public.show', [$businessSlug, $formSlug])->with('formSubmitted', true);
        }
        return redirect()->route('forms.public.show.legacy', $identifier)->with('formSubmitted', true);
    }
}
