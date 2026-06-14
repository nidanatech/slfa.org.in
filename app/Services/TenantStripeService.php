<?php

namespace App\Services;

use App\Models\Tenant;
use Stripe\StripeClient;
use Illuminate\Support\Facades\Log;

class TenantStripeService
{
    /**
     * Get a Stripe client for a specific tenant.
     */
    public function getClient(Tenant $tenant): ?StripeClient
    {
        $config = $tenant->stripeConfig;

        if (!$config || !$config->stripe_secret_key || !$config->is_active) {
            return null;
        }

        return new StripeClient($config->stripe_secret_key);
    }

    /**
     * Fetch products from tenant's Stripe account.
     */
    public function fetchProducts(Tenant $tenant)
    {
        $client = $this->getClient($tenant);
        if (!$client) {
            return [];
        }

        try {
            // Fetch products with their default prices
            $products = $client->products->all(['active' => true, 'expand' => ['data.default_price']]);
            return $products->data;
        } catch (\Exception $e) {
            Log::error("Stripe fetchProducts error for tenant {$tenant->id}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Create a product in tenant's Stripe account.
     */
    public function createProduct(Tenant $tenant, array $data)
    {
        $client = $this->getClient($tenant);
        if (!$client) {
            throw new \Exception("Stripe is not configured for this tenant.");
        }

        try {
            $product = $client->products->create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'default_price_data' => [
                    'currency' => $data['currency'] ?? 'usd',
                    'unit_amount' => $data['price'] * 100, // Stripe expects amounts in cents
                ],
            ]);

            return $product;
        } catch (\Exception $e) {
            Log::error("Stripe createProduct error for tenant {$tenant->id}: " . $e->getMessage());
            throw $e;
        }
    }
    /**
     * Sync products from Stripe to Sydkic.
     */
    public function syncProducts(Tenant $tenant)
    {
        $stripeProducts = $this->fetchProducts($tenant);
        
        // First pass: Create or update all products as main products initially
        // and also cache them in stripe_products table
        foreach ($stripeProducts as $sp) {
            $price = 0;
            $currency = 'USD';
            if (isset($sp->default_price) && is_object($sp->default_price)) {
                $price = $sp->default_price->unit_amount / 100;
                $currency = strtoupper($sp->default_price->currency);
            }

            // Cache in stripe_products table
            \App\Models\StripeProduct::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'stripe_product_id' => $sp->id
                ],
                [
                    'stripe_price_id' => is_object($sp->default_price) ? $sp->default_price->id : $sp->default_price,
                    'name' => $sp->name,
                    'description' => $sp->description,
                    'price' => $price,
                    'currency' => $currency,
                    'price_type' => is_object($sp->default_price) ? $sp->default_price->type : 'one_time',
                    'is_active' => $sp->active,
                    'metadata' => $sp->metadata ? $sp->metadata->toArray() : null
                ]
            );

            $parentId = $sp->metadata->parent_id ?? null;
            
            // If it has a parent_id, it's a variation. We'll handle it in the second pass.
            if ($parentId) continue;

            $this->updateOrCreateProduct($tenant, $sp);
        }

        // Second pass: Handle variations for App products
        foreach ($stripeProducts as $sp) {
            $parentId = $sp->metadata->parent_id ?? null;
            if (!$parentId) continue;

            // Find the parent product in Sydkic
            $parentProduct = \App\Models\Product::where('tenant_id', $tenant->id)
                ->where('stripe_product_id', $parentId)
                ->first();

            if ($parentProduct) {
                $parentProduct->update(['has_variations' => true]);
                
                $price = 0;
                $currency = 'USD';
                if (isset($sp->default_price) && is_object($sp->default_price)) {
                    $price = $sp->default_price->unit_amount / 100;
                    $currency = strtoupper($sp->default_price->currency);
                }

                \App\Models\ProductVariation::updateOrCreate(
                    [
                        'product_id' => $parentProduct->id,
                        'stripe_product_id' => $sp->id
                    ],
                    [
                        'tenant_id' => $tenant->id,
                        'name' => $sp->name,
                        'price' => $price,
                        'currency' => $currency,
                        'stripe_price_id' => is_object($sp->default_price) ? $sp->default_price->id : $sp->default_price,
                        'is_active' => $sp->active,
                        'attributes' => $sp->metadata ? $sp->metadata->toArray() : null
                    ]
                );
            } else {
                $this->updateOrCreateProduct($tenant, $sp);
            }
        }

        return count($stripeProducts);
    }

    protected function updateOrCreateProduct($tenant, $sp)
    {
        $price = 0;
        $currency = 'USD';
        
        if (isset($sp->default_price) && is_object($sp->default_price)) {
            $price = $sp->default_price->unit_amount / 100;
            $currency = strtoupper($sp->default_price->currency);
        }

        \App\Models\Product::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'stripe_product_id' => $sp->id
            ],
            [
                'name' => $sp->name,
                'description' => $sp->description,
                'price' => $price,
                'currency' => $currency,
                'stripe_price_id' => is_object($sp->default_price) ? $sp->default_price->id : $sp->default_price,
                'is_active' => $sp->active,
                'type' => ($sp->metadata->type ?? 'product') === 'service' ? 'service' : 'product',
                'meta' => $sp->metadata ? $sp->metadata->toArray() : null
            ]
        );
    }

    /**
     * Create a Checkout Session for a tenant.
     */
    public function createCheckoutSession(Tenant $tenant, array $lineItems, string $successUrl, string $cancelUrl, array $metadata = [])
    {
        $client = $this->getClient($tenant);
        if (!$client) {
            throw new \Exception("Stripe is not configured for this tenant.");
        }

        $mode = 'payment';
        foreach ($lineItems as $item) {
            $priceId = $item['price'] ?? null;
            if ($priceId) {
                // Try to check local cache first
                $sp = \App\Models\StripeProduct::where('tenant_id', $tenant->id)
                    ->where('stripe_price_id', $priceId)
                    ->first();
                
                if ($sp && $sp->price_type === 'recurring') {
                    $mode = 'subscription';
                    break;
                }
            }
        }

        return $client->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => $mode,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => array_merge($metadata, [
                'tenant_id' => $tenant->id,
            ]),
        ]);
    }
}
