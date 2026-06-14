<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\Event;
use App\Models\Opportunity;
use App\Models\Payment as SydkicPayment;
use Illuminate\Http\Request;
use Stripe\Webhook;
use Illuminate\Support\Facades\Log;

class TenantPaymentWebhookController extends Controller
{
    public function handle(Request $request, $tenantId)
    {
        Log::info("Tenant Stripe Webhook: Received request for tenant {$tenantId}");
        
        $tenant = Tenant::with('stripeConfig')->findOrFail($tenantId);
        $config = $tenant->stripeConfig;

        if (!$config || !$config->webhook_secret) {
            Log::error("Stripe webhook received for tenant {$tenantId} but no webhook secret configured.");
            return response()->json(['error' => 'Webhook secret not configured'], 400);
        }

        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $event = Webhook::constructEvent(
                $payload, $sigHeader, $config->webhook_secret
            );
            Log::info("Tenant Stripe Webhook: Event constructed successfully", ['type' => $event->type]);
        } catch (\UnexpectedValueException $e) {
            Log::error("Tenant Stripe Webhook: UnexpectedValueException", ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::error("Tenant Stripe Webhook: SignatureVerificationException", ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Handle the event
        if ($event->type === 'checkout.session.completed') {
            $this->handleCheckoutCompleted($tenant, $event->data->object);
        }

        return response()->json(['status' => 'success']);
    }

    protected function handleCheckoutCompleted(Tenant $tenant, $session)
    {
        $metadata = $session->metadata;
        $type = $metadata->type ?? null;
        $eventId = $metadata->event_id ?? null;
        $opportunityId = $metadata->opportunity_id ?? null;
        $contactId = $metadata->contact_id ?? null;

        Log::info("Tenant Stripe Webhook: Processing checkout.completed", [
            'tenant_id' => $tenant->id,
            'type' => $type,
            'event_id' => $eventId,
            'opportunity_id' => $opportunityId
        ]);

        // 1. Record the Payment in our DB
        $productId = $metadata->product_id ?? null;
        $variationId = $metadata->product_variation_id ?? null;

        if ($type === 'booking' && $eventId && (!$productId || !$variationId)) {
            $event = Event::find($eventId);
            if ($event) {
                $productId = $productId ?: $event->product_id;
                $variationId = $variationId ?: $event->product_variation_id;
            }
        } elseif ($type === 'form_submission' && $opportunityId && (!$productId || !$variationId)) {
            $opportunity = Opportunity::find($opportunityId);
            if ($opportunity) {
                $productId = $productId ?: $opportunity->product_id;
                $variationId = $variationId ?: $opportunity->product_variation_id;
            }
        }

        Log::info("Tenant Stripe Webhook: Attempting to create Payment record", [
            'tenant_id' => $tenant->id,
            'contact_id' => $contactId,
            'product_id' => $productId,
            'amount' => $session->amount_total / 100
        ]);

        try {
            SydkicPayment::create([
                'tenant_id' => $tenant->id,
                'contact_id' => $contactId,
                'product_id' => $productId,
                'product_variation_id' => $variationId,
                'event_id' => $eventId,
                'opportunity_id' => $opportunityId,
                'payment_date' => now(),
                'amount' => $session->amount_total / 100,
                'currency' => strtoupper($session->currency),
                'payment_method' => 'stripe',
                'transaction_id' => $session->payment_intent,
                'reference_number' => $session->id,
                'status' => 'completed',
                'type' => 'received',
                'notes' => "Payment from " . ($type === 'booking' ? 'Booking' : 'Lead Form')
            ]);
            Log::info("Tenant Stripe Webhook: Payment record created successfully");
        } catch (\Exception $e) {
            Log::error("Tenant Stripe Webhook: Failed to create Payment record", ['error' => $e->getMessage()]);
        }

        // 2. Update the related record
        if ($type === 'booking' && $eventId) {
            $event = Event::find($eventId);
            if ($event) {
                $event->update([
                    'status' => 'confirmed',
                    'payment_status' => 'paid'
                ]);
            }
        } elseif ($type === 'form_submission' && $opportunityId) {
            $opportunity = Opportunity::find($opportunityId);
            if ($opportunity) {
                $opportunity->update([
                    'payment_status' => 'paid',
                    'value_amount' => $session->amount_total / 100 // Update opportunity value
                ]);
            }
        }
    }
}
