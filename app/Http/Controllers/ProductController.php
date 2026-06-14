<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\TenantStripeService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::where('tenant_id', auth()->user()->tenant_id)
            ->with('variations')
            ->orderBy('name')
            ->get();

        $tenant = auth()->user()->tenant;
        $isStripeIntegrated = $tenant->stripeConfig()->where('is_active', true)->exists();
        $stripeProducts = [];
        
        if ($isStripeIntegrated) {
            $stripeProducts = \App\Models\StripeProduct::where('tenant_id', $tenant->id)
                ->orderBy('name')
                ->get();
        }

        return Inertia::render('Products/Index', [
            'products' => $products,
            'isStripeIntegrated' => $isStripeIntegrated,
            'stripeProducts' => $stripeProducts,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:product,service',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|max:3',
            'is_active' => 'boolean',
            'has_variations' => 'boolean',
            'variations' => 'nullable|array',
            'variations.*.name' => 'required_if:has_variations,true',
            'variations.*.price' => 'required_if:has_variations,true|numeric',
            'variations.*.sku' => 'nullable|string',
            'variations.*.attributes' => 'nullable|array',
            'stripe_product_id' => 'nullable|string|max:255',
            'stripe_price_id' => 'nullable|string|max:255',
            'variations.*.stripe_product_id' => 'nullable|string|max:255',
            'variations.*.stripe_price_id' => 'nullable|string|max:255',
        ]);

        $validated['tenant_id'] = auth()->user()->tenant_id;
        $validated['slug'] = Str::slug($validated['name']);

        // If has variations, main product should NOT have stripe IDs
        if ($validated['has_variations'] ?? false) {
            $validated['stripe_product_id'] = null;
            $validated['stripe_price_id'] = null;
        }

        $product = Product::create($validated);

        if ($request->has_variations && !empty($request->variations)) {
            foreach ($request->variations as $v) {
                $product->variations()->create([
                    'tenant_id' => auth()->user()->tenant_id,
                    'name' => $v['name'],
                    'price' => $v['price'],
                    'sku' => $v['sku'] ?? null,
                    'currency' => $validated['currency'],
                    'attributes' => $v['attributes'] ?? null,
                    'is_active' => true,
                    'stripe_product_id' => $v['stripe_product_id'] ?? null,
                    'stripe_price_id' => $v['stripe_price_id'] ?? null,
                ]);
            }
        }

        return redirect()->back()->with('success', 'Product created successfully.');
    }

    public function update(Request $request, Product $product)
    {
        $this->authorizeTenant($product);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:product,service',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|max:3',
            'is_active' => 'boolean',
            'has_variations' => 'boolean',
            'variations' => 'nullable|array',
            'variations.*.id' => 'nullable|exists:product_variations,id',
            'variations.*.name' => 'required_if:has_variations,true',
            'variations.*.price' => 'required_if:has_variations,true|numeric',
            'variations.*.sku' => 'nullable|string',
            'variations.*.attributes' => 'nullable|array',
            'stripe_product_id' => 'nullable|string|max:255',
            'stripe_price_id' => 'nullable|string|max:255',
            'variations.*.stripe_product_id' => 'nullable|string|max:255',
            'variations.*.stripe_price_id' => 'nullable|string|max:255',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        // If has variations, main product should NOT have stripe IDs
        if ($validated['has_variations'] ?? false) {
            $validated['stripe_product_id'] = null;
            $validated['stripe_price_id'] = null;
        }

        $product->update($validated);

        if ($request->has_variations && !empty($request->variations)) {
            $existingIds = collect($request->variations)->pluck('id')->filter()->toArray();
            $product->variations()->whereNotIn('id', $existingIds)->delete();

            foreach ($request->variations as $v) {
                $data = [
                    'tenant_id' => auth()->user()->tenant_id,
                    'name' => $v['name'],
                    'price' => $v['price'],
                    'sku' => $v['sku'] ?? null,
                    'currency' => $validated['currency'],
                    'attributes' => $v['attributes'] ?? null,
                    'is_active' => true,
                    'stripe_product_id' => $v['stripe_product_id'] ?? null,
                    'stripe_price_id' => $v['stripe_price_id'] ?? null,
                ];

                if (isset($v['id'])) {
                    $product->variations()->where('id', $v['id'])->update($data);
                } else {
                    $product->variations()->create($data);
                }
            }
        } else {
            $product->variations()->delete();
        }

        return redirect()->back()->with('success', 'Product updated successfully.');
    }

    public function show(Product $product)
    {
        $this->authorizeTenant($product);

        $product->load(['variations']);
        
        $payments = \App\Models\Payment::where('tenant_id', auth()->user()->tenant_id)
            ->where(function($query) use ($product) {
                $query->where('product_id', $product->id)
                      ->orWhereIn('product_variation_id', $product->variations->pluck('id'));
            })
            ->with(['contact', 'event', 'variation'])
            ->orderBy('payment_date', 'desc')
            ->get();

        return Inertia::render('Products/Show', [
            'product' => $product,
            'payments' => $payments,
        ]);
    }

    public function destroy(Product $product)
    {
        $this->authorizeTenant($product);
        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Product deleted successfully!');
    }

    public function sync(Request $request, TenantStripeService $stripeService)
    {
        $count = $stripeService->syncProducts(auth()->user()->tenant);

        return redirect()->route('products.index')
            ->with('success', "Synced {$count} products from Stripe.");
    }

    protected function authorizeTenant(Product $product)
    {
        if ($product->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }
    }
}
