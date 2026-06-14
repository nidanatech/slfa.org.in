<?php

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoicePdfGenerator
{
    public function generate(Invoice $invoice): string
    {
        $invoice->load(['contact', 'company', 'items', 'tenant.businessProfile', 'saleAgent', 'paymentMode', 'party', 'billingAddress', 'shippingAddress']);
        
        $pdf = Pdf::loadView('pdfs.invoice', [
            'invoice' => $invoice,
            'tenant' => $invoice->tenant,
            'businessProfile' => $invoice->tenant->businessProfile,
            'settings' => $invoice->tenant->settings['accounting'] ?? [],
        ]);

        $filename = "invoices/invoice-{$invoice->invoice_number}.pdf";
        Storage::put($filename, $pdf->output());

        return $filename;
    }

    public function download(Invoice $invoice)
    {
        $invoice->load(['contact', 'company', 'items', 'tenant.businessProfile', 'saleAgent', 'paymentMode', 'party', 'billingAddress', 'shippingAddress']);
        
        $pdf = Pdf::loadView('pdfs.invoice', [
            'invoice' => $invoice,
            'tenant' => $invoice->tenant,
            'businessProfile' => $invoice->tenant->businessProfile,
            'settings' => $invoice->tenant->settings['accounting'] ?? [],
        ]);

        return $pdf->download("Invoice-{$invoice->invoice_number}.pdf");
    }

    public function stream(Invoice $invoice)
    {
        $invoice->load(['contact', 'company', 'items', 'tenant.businessProfile', 'saleAgent', 'paymentMode', 'party', 'billingAddress', 'shippingAddress']);
        
        $pdf = Pdf::loadView('pdfs.invoice', [
            'invoice' => $invoice,
            'tenant' => $invoice->tenant,
            'businessProfile' => $invoice->tenant->businessProfile,
            'settings' => $invoice->tenant->settings['accounting'] ?? [],
        ]);

        return $pdf->stream("Invoice-{$invoice->invoice_number}.pdf");
    }
}
