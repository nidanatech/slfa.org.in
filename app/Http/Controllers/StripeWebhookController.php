<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Webhook;
use Stripe\Stripe;
use App\Services\RegistrationService;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    protected $registrationService;

    public function __construct(RegistrationService $registrationService)
    {
        $this->registrationService = $registrationService;
    }

    public function handle(Request $request)
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');
        $endpoint_secret = env('STRIPE_WEBHOOK_SECRET');

        try {
            $event = Webhook::constructEvent(
                $payload, $sig_header, $endpoint_secret
            );
        } catch (\UnexpectedValueException $e) {
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Handle the event
        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $this->handleCheckoutCompleted($session);
        }

        return response()->json(['status' => 'success']);
    }

    protected function handleCheckoutCompleted($session)
    {
        $metadata = $session->metadata;
        $email = $metadata->email ?? $session->customer_details->email;

        // Existing flow: metadata contains user_id/tenant_id
        if (isset($metadata->user_id) && isset($metadata->tenant_id)) {
            $user = User::find($metadata->user_id);
            if ($user && $user->tenant) {
                $this->registrationService->activateSubscription($user->tenant, $metadata->plan_slug, $session->subscription);
                Log::info("Subscription activated for existing user: " . $user->email);
                return;
            }
        }

        // Guest flow/Fallback: check if user exists by email
        $user = User::where('email', $email)->first();
        if ($user && $user->tenant) {
            $this->registrationService->activateSubscription($user->tenant, $metadata->plan_slug, $session->subscription);
            Log::info("Subscription activated for existing user (fallback): " . $user->email);
            return;
        }

        // New user registration (Legacy guest flow)
        $data = [
            'name' => $metadata->name ?? $session->customer_details->name,
            'email' => $email,
            'password' => $metadata->password ?? '',
            'stripe_id' => $session->subscription,
        ];

        try {
            $this->registrationService->registerUser($data, $metadata->plan_slug);
            Log::info("User registered via Stripe Webhook: " . $email);
        } catch (\Exception $e) {
            Log::error("Failed to register user via Stripe Webhook: " . $e->getMessage());
        }
    }
}
