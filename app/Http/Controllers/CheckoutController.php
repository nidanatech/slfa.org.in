<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Plan;
use App\Services\RegistrationService;
use Illuminate\Support\Facades\Auth;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class CheckoutController extends Controller
{
    protected $registrationService;

    public function __construct(RegistrationService $registrationService)
    {
        $this->registrationService = $registrationService;
    }

    public function select(Request $request)
    {
        $planSlug = $request->input('plan_slug');
        $plan = Plan::where('slug', $planSlug)->firstOrFail();

        // Check if we have a pending registration (Legacy/Guest flow)
        $registrationData = session('pending_registration');

        // Internal flow: user is logged in
        if (Auth::check()) {
            $user = Auth::user();
            
            // If Free Plan -> Assign immediately and go to onboarding
            if ($plan->price_monthly == 0) {
                // Assign plan logic (similar to RegistrationService but for existing user)
                \App\Models\Subscription::updateOrCreate(
                    ['tenant_id' => $user->tenant_id],
                    [
                        'plan_id' => $plan->id,
                        'type' => 'default',
                        'stripe_id' => 'free_' . uniqid(),
                        'stripe_status' => 'active',
                        'stripe_price' => $plan->stripe_price_id,
                        'quantity' => 1,
                    ]
                );
                return redirect()->route('onboarding.index');
            }

            // If Paid Plan -> Create Stripe Checkout Session
            Stripe::setApiKey(env('STRIPE_SECRET'));

            $checkoutSession = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price' => $plan->stripe_price_id,
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => route('checkout.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('subscription.select'),
                'customer_email' => $user->email,
                'metadata' => [
                    'user_id' => $user->id,
                    'tenant_id' => $user->tenant_id,
                    'plan_slug' => $planSlug,
                ],
            ]);

            return redirect($checkoutSession->url);
        }

        // Guest flow (Fallback)
        if (!$registrationData) {
            return redirect()->route('register')->with('error', 'Please register first.');
        }

        // ... existing legacy guest code (if still needed)
        return redirect()->route('pricing');
    }

    public function success(Request $request)
    {
        // verify session etc (can be simplified if we rely on webhook)
        return redirect()->route('onboarding.index')->with('success', 'Plan selected successfully! Please complete your profile.');
    }
}
