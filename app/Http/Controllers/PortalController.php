<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\InvoicePdfGenerator;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PortalController extends Controller
{
    protected $pdfGenerator;

    public function __construct(InvoicePdfGenerator $pdfGenerator)
    {
        $this->pdfGenerator = $pdfGenerator;
    }

    public function showInvoice($token)
    {
        $invoice = Invoice::where('portal_token', $token)
            ->with(['contact', 'company', 'items', 'tenant', 'payments'])
            ->firstOrFail();

        // Track view
        if (!$invoice->viewed_at) {
            $invoice->markAsViewed();
        }

        return Inertia::render('Portal/Invoice', [
            'invoice' => $invoice,
            'tenant' => $invoice->tenant,
            'settings' => $invoice->tenant->settings['accounting'] ?? [],
        ]);
    }

    public function downloadInvoicePdf($token)
    {
        $invoice = Invoice::where('portal_token', $token)
            ->with(['contact', 'company', 'items', 'tenant'])
            ->firstOrFail();

        return $this->pdfGenerator->download($invoice);
    }
}
