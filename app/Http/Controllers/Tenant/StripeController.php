<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\TenantStripeConfig;
use App\Services\TenantStripeService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StripeController extends Controller
{
    protected $stripeService;

    public function __construct(TenantStripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Display Stripe settings page.
     */
    public function settings(Request $request)
    {
        $tenant = $request->user()->tenant;
        $config = $tenant->stripeConfig;

        return Inertia::render('Settings/StripeSettings', [
            'tenant_id' => $tenant->id,
            'config' => $config ? [
                'stripe_public_key' => $config->stripe_public_key,
                'stripe_secret_key' => $config->stripe_secret_key ? '••••••••' : null,
                'webhook_secret' => $config->webhook_secret,
                'is_active' => $config->is_active,
            ] : null,
        ]);
    }

    /**
     * Update Stripe settings.
     */
    public function updateSettings(Request $request)
    {
        $tenant = $request->user()->tenant;
        
        $validated = $request->validate([
            'stripe_public_key' => 'nullable|string',
            'stripe_secret_key' => 'nullable|string',
            'webhook_secret' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $config = TenantStripeConfig::updateOrCreate(
            ['tenant_id' => $tenant->id],
            array_filter([
                'stripe_public_key' => $validated['stripe_public_key'],
                'stripe_secret_key' => $validated['stripe_secret_key'] !== '••••••••' ? $validated['stripe_secret_key'] : null,
                'webhook_secret' => $validated['webhook_secret'],
                'is_active' => $validated['is_active'],
            ])
        );

        return back()->with('success', 'Stripe settings updated successfully.');
    }

    /**
     * List products from tenant's Stripe account.
     */
    public function products(Request $request)
    {
        $tenant = $request->user()->tenant;
        $products = $this->stripeService->fetchProducts($tenant);

        return Inertia::render('Settings/Stripe/Products', [
            'products' => $products,
        ]);
    }

    /**
     * Store a new product in Stripe.
     */
    public function storeProduct(Request $request)
    {
        $tenant = $request->user()->tenant;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|length:3',
        ]);

        try {
            $this->stripeService->createProduct($tenant, $validated);
            return back()->with('success', 'Product created successfully in Stripe.');
        } catch (\Exception $e) {
            return back()->withErrors(['stripe' => $e->getMessage()]);
        }
    }
}
