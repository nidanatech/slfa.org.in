<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Contact;
use App\Models\Company;
use App\Notifications\InvoiceSentNotification;
use App\Services\InvoicePdfGenerator;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    protected $pdfGenerator;

    public function __construct(InvoicePdfGenerator $pdfGenerator)
    {
        $this->pdfGenerator = $pdfGenerator;
    }

    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $invoices = Invoice::where('tenant_id', $tenantId)
            ->with(['party', 'contact', 'company'])
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                      ->orWhereHas('party', function ($pq) use ($search) {
                          $pq->where('name', 'like', "%{$search}%");
                      })
                      ->orWhereHas('contact', function ($cq) use ($search) {
                          $cq->where('name', 'like', "%{$search}%");
                      });
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    public function create()
    {
        $tenantId = auth()->user()->tenant_id;

        // Fetch Parties
        $parties = \App\Models\Party::where('tenant_id', $tenantId)
            ->with(['company', 'addresses', 'contacts'])
            ->get();
            
        // Fetch Contacts that are NOT linked to any party (or just all contacts? User said "show all contacts and parties")
        // If we show all, we need to handle if they are already a party.
        // Easiest is to fetch all contacts and let frontend filter or just show them.
        // But if they are already a party, we should probably prefer the Party record.
        // Let's fetch contacts that normally valid for invoicing (Customers/Leads)
        // Check filtering in Frontend or just fetch all.
        
        $contacts = Contact::where('tenant_id', $tenantId)
            ->doesntHave('parties') // Only show contacts that are NOT yet parties to avoid confusion?
            // User said "show all contacts and parties". 
            // If I show a contact that IS a party, and they select it, it should just select the party?
            // But the dropdown is for "Select Customer". 
            // Better: Show Parties (Customers). Show Contacts (Potential Customers).
            // If they pick a Contact that is already part of a Party, they should have picked the Party.
            // So `doesntHave('parties')` is cleaner.
            ->get();        

        $companies = []; 

        $tenant = auth()->user()->tenant;
        $settings = $tenant->settings['accounting'] ?? [];
        $businessProfile = \App\Models\BusinessProfile::where('tenant_id', $tenantId)->first();

        $saleAgents = \App\Models\User::where('tenant_id', $tenantId)->get();
        $paymentModes = \App\Models\PaymentMode::where('tenant_id', $tenantId)->where('is_active', true)->get();
        $products = \App\Models\Product::where('tenant_id', $tenantId)->with('variations')->get();

        return Inertia::render('Invoices/Create', [
            'parties' => $parties,
            'contacts' => $contacts,
            'companies' => $companies,
            'settings' => $settings,
            'businessProfile' => $businessProfile,
            'saleAgents' => $saleAgents,
            'paymentModes' => $paymentModes,
            'products' => $products,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'party_id' => 'required|exists:parties,id',
            'contact_id' => 'nullable|exists:contacts,id',
            'billing_address_id' => 'nullable|exists:addresses,id',
            'shipping_address_id' => 'nullable|exists:addresses,id',
            'sale_agent_id' => 'nullable|exists:users,id',
            'payment_mode_id' => 'nullable|exists:payment_modes,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'currency' => 'required|string|max:3',
            'tax_type' => 'required|in:none,us_sales_tax,india_gst',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.product_variation_id' => 'nullable|exists:product_variations,id',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.hsn_sac_code' => 'nullable|string',
        ]);

        $tenantId = auth()->user()->tenant_id;
        $tenant = auth()->user()->tenant;
        $settings = $tenant->settings['accounting'] ?? [];

        // 1. Resolve Addresses (from IDs or request input if allowing manual override, 
        // but robust design prefers IDs. We'll support IDs for now as per plan).
        // If IDs are provided, fetch them. If raw data, well, the UI should preferably create Addresses first?
        // For simplicity and robustness, we assume the UI creates/selects addresses or we allow standard 'billing_address' array for now and assume migration later?
        // NO, the plan says "Sidebar: Search Party -> Load Addresses". User selects address.
        // So request should have 'billing_address_id' and 'shipping_address_id'.
        
        $billingAddressId = $request->input('billing_address_id');
        $shippingAddressId = $request->input('shipping_address_id');
        
        $billingAddressSnapshot = null;
        $shippingAddressSnapshot = null;
        
        if ($billingAddressId) {
             $addr = \App\Models\Address::find($billingAddressId);
             $billingAddressSnapshot = $addr ? $addr->toArray() : null;
        }
        if ($shippingAddressId) {
             $addr = \App\Models\Address::find($shippingAddressId);
             $shippingAddressSnapshot = $addr ? $addr->toArray() : null;
        }

        // Snapshot Structure
        $addressSnapshot = [
            'billing' => $billingAddressSnapshot,
            'shipping' => $shippingAddressSnapshot
        ];
        
        // Fetch Party for GSTIN snapshot
        $party = \App\Models\Party::find($validated['party_id']);
        $gstinSnapshot = $party ? $party->gstin : null;

        $invoice = Invoice::create([
            'tenant_id' => $tenantId,
            'party_id' => $validated['party_id'],
            'contact_id' => $request->input('contact_id'), // Optional contact (recipient)
            'billing_address_id' => $billingAddressId,
            'shipping_address_id' => $shippingAddressId,
            'sale_agent_id' => $request->input('sale_agent_id'),
            'payment_mode_id' => $request->input('payment_mode_id'),
            'gstin_snapshot' => $gstinSnapshot,
            'address_snapshot' => $addressSnapshot, // JSON cast handled by model
            
            // Legacy/Duplicate fields for safety or backward compat until full migration
            'company_id' => $party->company_id ?? null,
            
            'invoice_date' => $validated['invoice_date'],
            'due_date' => $validated['due_date'],
            'currency' => $validated['currency'],
            'tax_type' => $validated['tax_type'],
            'discount_amount' => $validated['discount_amount'] ?? 0,
            'notes' => $validated['notes'],
            'terms' => $validated['terms'] ?? $settings['invoice_terms'] ?? null,
            'footer' => $settings['invoice_footer'] ?? null,
            'status' => 'draft',
            
            // We can still populate these for easier display if we want, or rely on snapshot
            'billing_address' => $billingAddressSnapshot, 
            'shipping_address' => $shippingAddressSnapshot,
        ]);

        foreach ($validated['items'] as $index => $item) {
            $invoice->items()->create([
                'product_id' => $item['product_id'] ?? null,
                'product_variation_id' => $item['product_variation_id'] ?? null,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'tax_rate' => $item['tax_rate'] ?? 0,
                'hsn_sac_code' => $item['hsn_sac_code'] ?? null,
                'sort_order' => $index,
            ]);
        }

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice created successfully!');
    }

    public function show(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load(['party', 'contact', 'company', 'items.product', 'items.variation', 'payments', 'billingAddress', 'shippingAddress']);

        return Inertia::render('Invoices/Show', [
            'invoice' => $invoice,
        ]);
    }

    public function edit(Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        if (in_array($invoice->status, ['paid', 'cancelled'])) {
            return back()->with('error', 'Cannot edit paid or cancelled invoices.');
        }

        $tenantId = auth()->user()->tenant_id;

        $parties = \App\Models\Party::where('tenant_id', $tenantId)
            ->with(['company', 'addresses', 'contacts'])
            ->get();

        $contacts = Contact::where('tenant_id', $tenantId)
            ->doesntHave('parties')
            ->get();

        $invoice->load('items', 'party', 'billingAddress', 'shippingAddress'); // Load new relations

        $saleAgents = \App\Models\User::where('tenant_id', $tenantId)->get();
        $paymentModes = \App\Models\PaymentMode::where('tenant_id', $tenantId)->where('is_active', true)->get();
        $products = \App\Models\Product::where('tenant_id', $tenantId)->with('variations')->get();

        return Inertia::render('Invoices/Edit', [
            'invoice' => $invoice,
            'parties' => $parties,
            'contacts' => $contacts,
            'companies' => [], // Remove
            'businessProfile' => \App\Models\BusinessProfile::where('tenant_id', $tenantId)->first(),
            'saleAgents' => $saleAgents,
            'paymentModes' => $paymentModes,
            'products' => $products,
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        if (in_array($invoice->status, ['paid', 'cancelled'])) {
            return back()->with('error', 'Cannot update paid or cancelled invoices.');
        }

        $validated = $request->validate([
            'party_id' => 'required|exists:parties,id',
            'contact_id' => 'nullable|exists:contacts,id',
            'company_id' => 'nullable', // potentially unused now but keep for safety
            'billing_address_id' => 'nullable|exists:addresses,id',
            'shipping_address_id' => 'nullable|exists:addresses,id',
            'sale_agent_id' => 'nullable|exists:users,id',
            'payment_mode_id' => 'nullable|exists:payment_modes,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'currency' => 'required|string|max:3',
            'tax_type' => 'required|in:none,us_sales_tax,india_gst',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.product_variation_id' => 'nullable|exists:product_variations,id',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.hsn_sac_code' => 'nullable|string',
        ]);

        // Address resolution logic (simplified for update, ideally reused)
        $billingAddressId = $request->input('billing_address_id');
        $shippingAddressId = $request->input('shipping_address_id');
        
        // Update snapshots if addresses changed? 
        // For now, let's update the IDs. Snapshots should really be immutable but if user expects update...
        // We'll update IDs.
        
        $invoice->update([
            'party_id' => $validated['party_id'],
            'contact_id' => $validated['contact_id'],
            'billing_address_id' => $billingAddressId,
            'shipping_address_id' => $shippingAddressId,
            'sale_agent_id' => $validated['sale_agent_id'] ?? null,
            'payment_mode_id' => $validated['payment_mode_id'] ?? null,
            // 'company_id' => $validated['company_id'] === 'none' ? null : $validated['company_id'], // Legacy
            'invoice_date' => $validated['invoice_date'],
            'due_date' => $validated['due_date'],
            'currency' => $validated['currency'],
            'tax_type' => $validated['tax_type'],
            'discount_amount' => $validated['discount_amount'] ?? 0,
            'notes' => $validated['notes'],
            'terms' => $validated['terms'],
        ]);

        // Delete existing items and recreate
        $invoice->items()->delete();

        foreach ($validated['items'] as $index => $item) {
            $invoice->items()->create([
                'product_id' => $item['product_id'] ?? null,
                'product_variation_id' => $item['product_variation_id'] ?? null,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'tax_rate' => $item['tax_rate'] ?? 0,
                'hsn_sac_code' => $item['hsn_sac_code'] ?? null,
                'sort_order' => $index,
            ]);
        }

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice updated successfully!');
    }

    public function destroy(Invoice $invoice)
    {
        $this->authorize('delete', $invoice);

        if ($invoice->status === 'paid') {
            return back()->with('error', 'Cannot delete paid invoices.');
        }

        $invoice->delete();

        return redirect()->route('invoices.index')
            ->with('success', 'Invoice deleted successfully!');
    }

    public function send(Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        // Generate PDF
        $pdfPath = $this->pdfGenerator->generate($invoice);

        // Send invoice
        $invoice->send();

        // Send email notification
        $contact = $invoice->contact;
        if ($contact->primary_email) {
            $contact->notify(new InvoiceSentNotification($invoice, $pdfPath));
        }

        return back()->with('success', 'Invoice sent successfully!');
    }

    public function downloadPdf(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        return $this->pdfGenerator->download($invoice);
    }

    public function markPaid(Request $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        $invoice->markAsPaid();

        return back()->with('success', 'Invoice marked as paid!');
    }
}
