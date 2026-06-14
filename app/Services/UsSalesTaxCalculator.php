<?php

namespace App\Services;

use App\Models\TaxRate;

class UsSalesTaxCalculator
{
    public function calculate(float $subtotal, string $state, int $tenantId): array
    {
        $taxRate = TaxRate::where('tenant_id', $tenantId)
            ->where('country', 'US')
            ->where('state_province', $state)
            ->where('tax_type', 'us_sales_tax')
            ->where('is_active', true)
            ->first();

        if (!$taxRate) {
            return [
                'tax_amount' => 0,
                'tax_rate' => 0,
                'tax_details' => null,
            ];
        }

        $taxAmount = round($subtotal * ($taxRate->rate / 100), 2);

        return [
            'tax_amount' => $taxAmount,
            'tax_rate' => $taxRate->rate,
            'tax_details' => [
                'tax_name' => $taxRate->name,
                'state' => $state,
                'rate' => $taxRate->rate,
            ],
        ];
    }
}
