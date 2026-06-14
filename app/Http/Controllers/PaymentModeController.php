<?php

namespace App\Http\Controllers;

use App\Models\PaymentMode;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentModeController extends Controller
{
    public function index()
    {
        $tenantId = auth()->user()->tenant_id;
        $paymentModes = PaymentMode::where('tenant_id', $tenantId)->get();
        return Inertia::render('Settings/PaymentModes/Index', [
            'paymentModes' => $paymentModes
        ]);
    }

    public function store(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'show_on_pdf' => 'boolean',
            'is_default' => 'boolean',
        ]);

        if ($validated['is_default'] ?? false) {
            // Unset other defaults
            PaymentMode::where('tenant_id', $tenantId)->update(['is_default' => false]);
        }

        PaymentMode::create([
            'tenant_id' => $tenantId,
            ...$validated
        ]);

        return back()->with('success', 'Payment mode created successfully.');
    }

    public function update(Request $request, PaymentMode $paymentMode)
    {
        $tenantId = auth()->user()->tenant_id;
        if ($paymentMode->tenant_id !== $tenantId) abort(403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'show_on_pdf' => 'boolean',
            'is_default' => 'boolean',
        ]);

        if ($validated['is_default'] ?? false) {
            PaymentMode::where('tenant_id', $tenantId)->where('id', '!=', $paymentMode->id)->update(['is_default' => false]);
        }

        $paymentMode->update($validated);

        return back()->with('success', 'Payment mode updated successfully.');
    }

    public function destroy(PaymentMode $paymentMode)
    {
        if ($paymentMode->tenant_id !== auth()->user()->tenant_id) abort(403);
        $paymentMode->delete();
        return back()->with('success', 'Payment mode deleted successfully.');
    }
}
