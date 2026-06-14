<?php

namespace App\Services;

use App\Models\TaxRate;

class IndiaGstCalculator
{
    public function calculate(array $items, string $sellerState, string $buyerState, int $tenantId): array
    {
        $isInterState = ($sellerState !== $buyerState);
        $totalTax = 0;
        $itemsWithTax = [];

        foreach ($items as $item) {
            $itemSubtotal = $item['quantity'] * $item['unit_price'];
            $taxRate = $item['tax_rate'] ?? 0;
            
            if ($taxRate > 0) {
                $itemTax = round($itemSubtotal * ($taxRate / 100), 2);
                
                if ($isInterState) {
                    // IGST = full rate
                    $item['tax_details'] = [
                        'type' => 'IGST',
                        'igst_rate' => $taxRate,
                        'igst_amount' => $itemTax,
                    ];
                } else {
                    // CGST + SGST = half each
                    $halfRate = $taxRate / 2;
                    $halfAmount = $itemTax / 2;
                    
                    $item['tax_details'] = [
                        'type' => 'CGST+SGST',
                        'cgst_rate' => $halfRate,
                        'cgst_amount' => round($halfAmount, 2),
                        'sgst_rate' => $halfRate,
                        'sgst_amount' => round($halfAmount, 2),
                    ];
                }
                
                $item['tax_amount'] = $itemTax;
                $totalTax += $itemTax;
            }
            
            $itemsWithTax[] = $item;
        }

        return [
            'total_tax' => $totalTax,
            'items' => $itemsWithTax,
            'is_inter_state' => $isInterState,
        ];
    }

    public function getGstRates(int $tenantId): array
    {
        return TaxRate::where('tenant_id', $tenantId)
            ->where('country', 'IN')
            ->where('is_active', true)
            ->whereIn('tax_type', ['india_gst_cgst', 'india_gst_sgst', 'india_gst_igst'])
            ->get()
            ->groupBy('tax_type')
            ->toArray();
    }
}
