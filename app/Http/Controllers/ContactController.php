<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Channel;
use App\Models\CustomField;
use App\Models\CustomFieldObject;
use App\Models\CustomFieldValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $query = Contact::with(['company', 'tags', 'channel']);

        // Fetch Custom Fields for Contacts
        $contactObject = CustomFieldObject::where('name', 'contact')->first();
        $customFields = $contactObject ? CustomField::where('object_id', $contactObject->id)
            ->where('tenant_id', \Illuminate\Support\Facades\Auth::user()->tenant_id)
            ->get() : [];

        if ($request->has('filters') && is_array($request->input('filters'))) {
            foreach ($request->input('filters') as $filter) {
                if (!isset($filter['field']) || !isset($filter['operator']) || !isset($filter['value'])) {
                    continue;
                }

                $field = $filter['field'];
                $operator = $filter['operator'];
                $value = $filter['value'];

                if ($field === 'tags') {
                    if ($operator === 'is_empty') {
                        $query->doesntHave('tags');
                        continue;
                    }
                    if ($operator === 'is_not_empty') {
                        $query->has('tags');
                        continue;
                    }

                    $query->whereHas('tags', function ($q) use ($value, $operator) {
                         if ($operator === 'contains' || $operator === 'equals') {
                             $q->where('name', 'like', '%' . $value . '%');
                         }
                    });
                    continue;
                }
                
                if ($field === 'company_id') {
                     // Allow searching by company name if user types text
                     if (!is_numeric($value)) {
                         $query->whereHas('company', function($q) use ($value) {
                             $q->where('name', 'like', '%' . $value . '%');
                         });
                         continue;
                     }
                }

                if (str_starts_with($field, 'cf_')) {
                    $fieldId = substr($field, 3);
                    $contactObjectId = \App\Models\CustomFieldObject::where('name', 'contact')->value('id');

                    if ($operator === 'is_empty') {
                        $query->whereDoesntHave('customFieldValues', function ($q) use ($fieldId, $contactObjectId) {
                            $q->where('object_id', $contactObjectId)->where('field_id', $fieldId);
                        });
                        continue;
                    }
                    if ($operator === 'is_not_empty') {
                        $query->whereHas('customFieldValues', function ($q) use ($fieldId, $contactObjectId) {
                            $q->where('object_id', $contactObjectId)->where('field_id', $fieldId);
                        });
                        continue;
                    }

                    $query->whereHas('customFieldValues', function ($q) use ($fieldId, $value, $operator, $contactObjectId) {
                        $q->where('object_id', $contactObjectId)
                          ->where('field_id', $fieldId);
                        
                        // Value matching logic
                        if (in_array($operator, ['contains', 'starts_with', 'ends_with'])) {
                             $q->where('value_text', 'like', $operator === 'contains' ? "%$value%" : ($operator === 'starts_with' ? "$value%" : "%$value"));
                        } else {
                             $q->where('value_text', $value)
                               ->orWhere('value_number', $value)
                               ->orWhere('value_date', $value)
                               ->orWhere('value_boolean', $value);
                        }
                    });
                    continue;
                }

                switch ($operator) {
                    case 'contains':
                        $query->where($field, 'like', '%' . $value . '%');
                        break;
                    case 'equals':
                        $query->where($field, $value);
                        break;
                    case 'starts_with':
                        $query->where($field, 'like', $value . '%');
                        break;
                    case 'ends_with':
                        $query->where($field, 'like', '%' . $value);
                        break;
                    case 'is_empty':
                        $query->whereNull($field)->orWhere($field, '');
                        break;
                    case 'is_not_empty':
                        $query->whereNotNull($field)->where($field, '!=', '');
                        break;
                }
            }
        }

        // Search functionality (global search)
        if ($request->has('search') && $request->input('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('first_name', 'like', '%' . $search . '%')
                  ->orWhere('last_name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%')
                  ->orWhere('phones', 'like', '%' . $search . '%')
                  ->orWhere('emails', 'like', '%' . $search . '%');
            });
        }

        // Fetch Companies for dropdown
        $companies = \App\Models\Company::where('tenant_id', \Illuminate\Support\Facades\Auth::user()->tenant_id)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('Contacts/Index', [
            'contacts' => $query->latest()->paginate(10)->withQueryString(),
            'companies' => $companies,
            'filters' => $request->input('filters', []),
            'search' => $request->input('search'),
            'customFields' => $customFields,
            'currentTab' => \Illuminate\Support\Facades\Route::currentRouteName() === 'contacts.customers' ? 'customers' : 'contacts',
        ]);
    }

    public function customers(Request $request)
    {
        $filters = $request->input('filters', []);
        // Check if customer filter already exists to avoid duplication if user reloads
        $hasCustomerFilter = false;
        foreach ($filters as $filter) {
            if ($filter['field'] === 'contact_types' && $filter['value'] === 'customer') {
                $hasCustomerFilter = true;
                break;
            }
        }
        
        if (!$hasCustomerFilter) {
            $filters[] = ['field' => 'contact_types', 'operator' => 'contains', 'value' => 'customer'];
            $request->merge(['filters' => $filters]);
        }
        
        return $this->index($request);
    }

    public function show(Contact $contact)
    {
        // Debug logging
        \Log::info('Contact Show Access Attempt', [
            'contact_id' => $contact->id,
            'contact_tenant_id' => $contact->tenant_id,
            'user_id' => \Illuminate\Support\Facades\Auth::id(),
            'user_tenant_id' => \Illuminate\Support\Facades\Auth::user()->tenant_id ?? 'null',
        ]);
        
        // Ensure tenant access
        if ($contact->tenant_id !== \Illuminate\Support\Facades\Auth::user()->tenant_id) {
            abort(403);
        }

        $contact->load(['company', 'tags', 'customFieldValues', 'channel', 'notes.author', 'notes.attachments', 'tasks.owner', 'tasks.creator', 'events', 'conversations.messages']);

        // Fetch Activities
        $activities = \App\Models\Activity::where('contact_id', $contact->id)
            ->with(['actor:id,name,profile_image'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Fetch Custom Fields definitions
        $contactObject = CustomFieldObject::where('name', 'contact')->first();
        $customFields = $contactObject ? CustomField::where('object_id', $contactObject->id)
            ->where('tenant_id', \Illuminate\Support\Facades\Auth::user()->tenant_id)
            ->get() : [];

         // Fetch Companies for dropdown
        $companies = \App\Models\Company::where('tenant_id', \Illuminate\Support\Facades\Auth::user()->tenant_id)
            ->select('id', 'name', 'email', 'phone', 'website', 'address', 'city', 'state', 'country', 'zip', 'description')
            ->orderBy('name')
            ->get();

        // Fetch Users for assignment
        $users = \App\Models\User::where('tenant_id', \Illuminate\Support\Facades\Auth::user()->tenant_id)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        // Fetch all tags for autocomplete
        $allTags = \App\Models\Tag::where('tenant_id', \Illuminate\Support\Facades\Auth::user()->tenant_id)
            ->orderBy('name')
            ->get();

        // Fetch calendars for the tenant
        $calendars = \App\Models\Calendar::where('tenant_id', \Illuminate\Support\Facades\Auth::user()->tenant_id)->get();

        // Fetch accounting data if contact is a customer
        $invoices = [];
        $payments = [];
        $contracts = [];
        
        if (in_array('customer', $contact->contact_types ?? [])) {
            $invoices = \App\Models\Invoice::where('contact_id', $contact->id)
                ->with(['items'])
                ->orderBy('invoice_date', 'desc')
                ->get();
                
            $payments = \App\Models\Payment::where('contact_id', $contact->id)
                ->with(['invoice'])
                ->orderBy('payment_date', 'desc')
                ->get();
                
            $contracts = \App\Models\Contract::where('contact_id', $contact->id)
                ->orderBy('start_date', 'desc')
                ->get();
        }

        // Load Customer details
        $contact->load('customer');

        $tenant = \Illuminate\Support\Facades\Auth::user()->tenant;
        $terminology = $tenant->settings['terminology'] ?? [
            'customer_singular' => 'Customer',
            'customer_plural' => 'Customers',
        ];

        return Inertia::render('Contacts/Show', [
            'contact' => $contact,
            'terminology' => $terminology,
            'customFields' => $customFields,
            'companies' => $companies,
            'users' => $users,
            'activities' => $activities,
            'allTags' => $allTags,
            'calendars' => $calendars,
            'invoices' => $invoices,
            'payments' => $payments,
            'contracts' => $contracts,
        ]);
    }

    public function updateCustomer(\Illuminate\Http\Request $request, Contact $contact)
    {
        $contact->tenant_id == auth()->user()->tenant_id || abort(403);

        $validated = $request->validate([
            'billing_address' => 'nullable|array',
            'shipping_address' => 'nullable|array',
            'tax_id' => 'nullable|string',
            'currency' => 'nullable|string|size:3',
            'notes' => 'nullable|string',
        ]);

        $customerData = [
            'tenant_id' => $contact->tenant_id,
        ] + $validated;

        $contact->customer()->updateOrCreate(
            ['tenant_id' => $contact->tenant_id, 'contact_id' => $contact->id],
            $customerData
        );

        return back()->with('success', 'Customer details updated successfully.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'nullable|email', // For backward compatibility/simple form
            'phone' => 'nullable|string', // For backward compatibility/simple form
            'company_id' => 'nullable|exists:companies,id',
            
            // New fields
            'salutation' => 'nullable|string',
            'gender' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'marital_status' => 'nullable|string',
            'emails' => 'nullable|array',
            'phones' => 'nullable|array',
            'contact_type' => 'nullable|string',
            'time_zone' => 'nullable|string',
            'dnd_email' => 'boolean',
            'dnd_text' => 'boolean',
            'dnd_calls' => 'boolean',
            'dnd_inbound_calls_sms' => 'boolean',
            'image' => 'nullable|image|max:51200', // Image upload
        ]);

        $validated['tenant_id'] = \Illuminate\Support\Facades\Auth::user()->tenant_id;
        
        // Handle Image
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('contacts', 'public');
            $validated['contact_image'] = '/storage/' . $path;
        }

        // Normalize backward compat email/phone to arrays if array not provided
        if (empty($validated['emails']) && !empty($validated['email'])) {
            $validated['emails'] = [['type' => 'work', 'value' => $validated['email']]];
        }
        if (empty($validated['phones']) && !empty($validated['phone'])) {
            $validated['phones'] = [['type' => 'work', 'value' => $validated['phone']]];
        }
        
        // Channel ID can be null for manual contacts
        if ($request->has('channel_id')) {
            $validated['channel_id'] = $request->input('channel_id');
        } else {
             $validated['channel_id'] = null;
        } 
        
        $validated['external_id'] = \Illuminate\Support\Str::uuid();

        $contact = Contact::create($validated);

        // Handle Custom Fields
        if ($request->has('custom_fields') && is_array($request->input('custom_fields'))) {
            $contactObject = CustomFieldObject::where('name', 'contact')->first();
            if ($contactObject) {
                foreach ($request->input('custom_fields') as $fieldId => $value) {
                    $field = CustomField::find($fieldId);
                    if ($field && $field->object_id == $contactObject->id) {
                        $valueData = [
                            'tenant_id' => 1, // Mock
                            'object_id' => $contactObject->id,
                            'record_id' => $contact->id,
                            'field_id' => $field->id,
                        ];

                        // Determine mapping based on field_type
                        // field_type: text, number, email, select, date, boolean, file
                        $valueData['value_text'] = null;
                        $valueData['value_number'] = null;
                        $valueData['value_date'] = null;
                        $valueData['value_boolean'] = null;

                        switch ($field->field_type) {
                            case 'number':
                                $valueData['value_number'] = $value;
                                break;
                            case 'date':
                                $valueData['value_date'] = $value;
                                break;
                            case 'boolean':
                                $valueData['value_boolean'] = $value;
                                break;
                            default:
                                $valueData['value_text'] = is_array($value) ? json_encode($value) : $value;
                                break;
                        }
                        
                        CustomFieldValue::create($valueData);
                    }
                }
            }
        }

        return redirect()->back();
    }

    public function update(Request $request, Contact $contact)
    {
         $validated = $request->validate([
            'name' => 'nullable|string|max:255', // Allow updating full name directly
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'company_id' => 'nullable|exists:companies,id',
            
            'salutation' => 'nullable|string',
            'gender' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'marital_status' => 'nullable|string',
            'emails' => 'nullable|array',
            'phones' => 'nullable|array',
            'contact_type' => 'nullable|string',
            'time_zone' => 'nullable|string',
            'dnd_email' => 'boolean',
            'dnd_text' => 'boolean',
            'dnd_calls' => 'boolean',
            'dnd_inbound_calls_sms' => 'boolean',
            'image' => 'nullable|image|max:51200',
        ]);
        
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('contacts', 'public');
            $validated['contact_image'] = '/storage/' . $path;
        }

        // If 'name' is provided but first/last not provided, split them so Model hook doesn't overwrite name
        if (!empty($validated['name']) && empty($validated['first_name']) && empty($validated['last_name'])) {
            $parts = explode(' ', $validated['name'], 2);
            $validated['first_name'] = $parts[0] ?? '';
            $validated['last_name'] = $parts[1] ?? '';
        }

        $contact->update($validated);

        return redirect()->back();
    }
    public function destroy(Contact $contact)
    {
        $contact->delete();
        return redirect()->back();
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:contacts,id'
        ]);

        Contact::whereIn('id', $request->input('ids'))->delete();

        return redirect()->back()->with('success', 'Contacts deleted successfully.');
    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:contacts,id',
            'action' => 'required|string|in:add_tags,assign_company',
            'data' => 'required'
        ]);

        $ids = $request->input('ids');
        $action = $request->input('action');
        $data = $request->input('data');

        switch ($action) {
            case 'add_tags':
                $tagNames = is_string($data) ? explode(',', $data) : $data;
                $contacts = Contact::whereIn('id', $ids)->get();

                foreach ($tagNames as $tagName) {
                    $tagName = trim($tagName);
                    if (empty($tagName)) continue;

                    $slug = \Illuminate\Support\Str::slug($tagName);
                    $tag = \App\Models\Tag::firstOrCreate(
                        ['slug' => $slug, 'tenant_id' => \Illuminate\Support\Facades\Auth::user()->tenant_id],
                        ['name' => $tagName]
                    );

                    foreach ($contacts as $contact) {
                        // "store the tag in tags if it doesnt exist, else use the id... create a record in taggables"
                        // syncWithoutDetaching handles "if doesn't exist" on the pivot level
                        $result = $contact->tags()->syncWithoutDetaching([$tag->id]);

                        if (!empty($result['attached'])) {
                            \App\Models\Activity::create([
                                'tenant_id' => $contact->tenant_id,
                                'contact_id' => $contact->id,
                                'activity_type' => 'tag_added',
                                'source_table' => 'tags',
                                'source_id' => $tag->id,
                                'summary' => "Tag added: {$tag->name}",
                                'actor_id' => \Illuminate\Support\Facades\Auth::id(),
                            ]);
                        }
                    }
                }
                break;
            case 'assign_company':
                Contact::whereIn('id', $ids)->update(['company_id' => $data]);
                break;
        }

        return redirect()->back()->with('success', 'Bulk action completed.');
    }
    public function storeCompany(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $validated['tenant_id'] = \Illuminate\Support\Facades\Auth::user()->tenant_id;

        \App\Models\Company::create($validated);

        return redirect()->back()->with('success', 'Company created successfully.');
    }

    public function updateTags(Request $request, Contact $contact)
    {
        // Ensure tenant access
        if ($contact->tenant_id !== \Illuminate\Support\Facades\Auth::user()->tenant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'tag_ids' => 'required|array',
            'tag_ids.*' => 'exists:tags,id'
        ]);

        // Sync tags
        $contact->tags()->sync($validated['tag_ids']);

        // Log activity for each newly added tag
        $currentTagIds = $contact->tags()->pluck('tags.id')->toArray();
        $newTagIds = array_diff($validated['tag_ids'], $currentTagIds);
        
        foreach ($newTagIds as $tagId) {
            $tag = \App\Models\Tag::find($tagId);
            if ($tag) {
                \App\Models\Activity::create([
                    'tenant_id' => $contact->tenant_id,
                    'contact_id' => $contact->id,
                    'activity_type' => 'tag_added',
                    'source_table' => 'tags',
                    'source_id' => $tag->id,
                    'summary' => "Tag added: {$tag->name}",
                    'actor_id' => \Illuminate\Support\Facades\Auth::id(),
                ]);
            }
        }

        return redirect()->back()->with('success', 'Tags updated successfully.');
    }

    public function storeCustomer(Request $request)
    {
        $tenantId = \Illuminate\Support\Facades\Auth::user()->tenant_id;
        
        $validated = $request->validate([
            'type' => 'required|in:individual,company',
            
            // Common
            'name' => 'required_if:type,individual|nullable|string|max:255', // If individual, used as Contact Name
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            
            // Company Mode
            'company_name' => 'required_if:type,company|nullable|string|max:255',
            
            // Individual Mode (Linked Company)
            'company_id' => 'nullable|exists:companies,id',
            
            // Customer Details
            'billing_address' => 'nullable|array',
            'shipping_address' => 'nullable|array',
            'currency' => 'nullable|string|size:3',
            'tax_id' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $companyId = null;
            $contact = null;

            if ($request->input('type') === 'company') {
                // Create Company
                $company = \App\Models\Company::create([
                    'tenant_id' => $tenantId,
                    'name' => $validated['company_name'],
                    'email' => $validated['email'], // Company email
                    'phone' => $validated['phone'], // Company phone
                    'address' => $validated['billing_address']['line1'] ?? null,
                    'city' => $validated['billing_address']['city'] ?? null,
                    'state' => $validated['billing_address']['state'] ?? null,
                    'country' => $validated['billing_address']['country'] ?? null,
                    'zip' => $validated['billing_address']['postal_code'] ?? null,
                ]);
                $companyId = $company->id;
                
                // Create Primary Contact for Company
                // Use first_name/last_name if provided, otherwise derived from company?
                // Usually "Primary Contact" is a person. 
                // If user didn't provide person details, we might create a generic contact or require it.
                // Re-reading user request: "primary contact as the contact"
                // So we'll expect person details even for company, or default to "Representative".
                
                $contactName = ($validated['first_name'] ?? '') . ' ' . ($validated['last_name'] ?? '');
                $contactName = trim($contactName) ?: 'Primary Contact';
                
                $contact = Contact::create([
                    'tenant_id' => $tenantId,
                    'company_id' => $companyId,
                    'name' => $contactName,
                    'first_name' => $validated['first_name'] ?? '',
                    'last_name' => $validated['last_name'] ?? '',
                    'emails' => $validated['email'] ? [['type' => 'work', 'value' => $validated['email']]] : [],
                    'phones' => $validated['phone'] ? [['type' => 'work', 'value' => $validated['phone']]] : [],
                    'contact_types' => ['customer', 'lead'],
                ]);

            } else {
                // Individual
                $companyId = $validated['company_id'] ?? null;
                $contact = Contact::create([
                    'tenant_id' => $tenantId,
                    'company_id' => $companyId,
                    'name' => $validated['name'],
                    'first_name' => $validated['first_name'] ?? '',
                    'last_name' => $validated['last_name'] ?? '',
                    'emails' => $validated['email'] ? [['type' => 'personal', 'value' => $validated['email']]] : [],
                    'phones' => $validated['phone'] ? [['type' => 'mobile', 'value' => $validated['phone']]] : [],
                    'contact_types' => ['customer', 'lead'],
                ]);
            }

            // Create Customer Record
            \App\Models\Customer::create([
                'tenant_id' => $tenantId,
                'contact_id' => $contact->id,
                'billing_address' => $validated['billing_address'] ?? [],
                'shipping_address' => $validated['shipping_address'] ?? [],
                'currency' => $validated['currency'] ?? 'USD',
                'tax_id' => $validated['tax_id'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            \Illuminate\Support\Facades\DB::commit();
            return redirect()->back()->with('success', 'Customer added successfully.');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create customer: ' . $e->getMessage()]);
        }
    }


    public function convertToParty(Contact $contact)
    {
        // 1. Check if already linked to a party
        $existingParty = \App\Models\PartyContact::where('contact_id', $contact->id)->first();
        if ($existingParty) {
            return back()->with('error', 'Contact is already linked to a party.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($contact) {
            
            // If contact belongs to a company, create Party for Company if not exists
            if ($contact->company_id) {
                $company = $contact->company;
                $party = \App\Models\Party::firstOrCreate(
                    ['company_id' => $company->id],
                    [
                        'tenant_id' => $company->tenant_id,
                        'party_type' => 'company',
                        'name' => $company->name,
                        'currency' => 'USD',
                    ]
                );
                
                // Link contact
                $party->contacts()->syncWithoutDetaching([
                    $contact->id => ['is_primary' => false, 'receives_invoice' => true]
                ]);

                // Create Address for Company if new Party
                if ($party->wasRecentlyCreated && (!empty($company->address) || !empty($company->city))) {
                     $address = \App\Models\Address::create([
                        'tenant_id' => $company->tenant_id,
                        'line1' => $company->address ?? '',
                        'city' => $company->city ?? '',
                        'state' => $company->state ?? '',
                        'postal_code' => $company->zip ?? '',
                        'country' => $company->country ?? 'USA',
                    ]);
                    
                    \App\Models\PartyAddress::create(['party_id' => $party->id, 'address_id' => $address->id, 'address_type' => 'billing', 'is_default' => true]);
                    \App\Models\PartyAddress::create(['party_id' => $party->id, 'address_id' => $address->id, 'address_type' => 'shipping', 'is_default' => true]);
                }

            } else {
                // Individual Party
                $party = \App\Models\Party::create([
                    'tenant_id' => $contact->tenant_id,
                    'party_type' => 'individual',
                    'name' => $contact->name ?: 'Unknown',
                    'currency' => 'USD',
                ]);

                // Link Contact
                $party->contacts()->attach($contact->id, ['is_primary' => true, 'receives_invoice' => true]);

                // Address (check attributes or legacy customer record)
                // We'll rely on what's available on Contact model or if Customer record exists
                $customer = $contact->customer; 
                $billingAddr = $customer->billing_address ?? [];
                
                // If we have address on contact fields or customer fields
                // ... logic to extract address ...
                // For now, simple fallback
                if (!empty($contact->city) || !empty($billingAddr['line1'])) {
                     $address = \App\Models\Address::create([
                        'tenant_id' => $contact->tenant_id,
                        'line1' => $billingAddr['line1'] ?? $contact->address ?? '',
                        'city' => $billingAddr['city'] ?? $contact->city ?? '',
                        'state' => $billingAddr['state'] ?? $contact->state ?? '',
                        'postal_code' => $billingAddr['postal_code'] ?? $contact->zip ?? '',
                        'country' => $billingAddr['country'] ?? $contact->country ?? 'USA',
                    ]);
                    \App\Models\PartyAddress::create(['party_id' => $party->id, 'address_id' => $address->id, 'address_type' => 'billing', 'is_default' => true]);
                    \App\Models\PartyAddress::create(['party_id' => $party->id, 'address_id' => $address->id, 'address_type' => 'shipping', 'is_default' => true]);
                }
            }
        });

        return back()->with('success', 'Contact converted to Customer successfully.')
            ->with('converted_party_id', $party->id ?? $existingParty->party_id ?? null);
    }

    public function toggleTaskAutomation(Request $request, Contact $contact)
    {
        // Ensure tenant access
        if ($contact->tenant_id !== \Illuminate\Support\Facades\Auth::user()->tenant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'enabled' => 'required|boolean'
        ]);

        $contact->update([
            'task_automation_enabled' => $validated['enabled']
        ]);

        return response()->json([
            'success' => true,
            'enabled' => $contact->task_automation_enabled
        ]);
    }

    public function toggleSyncEnabled(Request $request, Contact $contact)
    {
        // Ensure tenant access
        if ($contact->tenant_id !== \Illuminate\Support\Facades\Auth::user()->tenant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'enabled' => 'required|boolean'
        ]);

        $contact->update([
            'sync_enabled' => $validated['enabled']
        ]);

        return response()->json([
            'success' => true,
            'enabled' => $contact->sync_enabled
        ]);
    }

    public function getChannelContacts(Request $request, Channel $channel)
    {
        // Ensure tenant access
        if ($channel->tenant_id !== \Illuminate\Support\Facades\Auth::user()->tenant_id) {
            abort(403);
        }

        $query = Contact::where('channel_id', $channel->id);

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phones', 'like', "%{$search}%")
                  ->orWhere('external_id', 'like', "%{$search}%");
            });
        }

        $contacts = $query->orderBy('name')->paginate(20);

        return response()->json($contacts);
    }

    public function generateTasksManually(Request $request, Channel $channel)
    {
        Log::info("Manual task generation requested for channel: {$channel->id} (Tenant: {$channel->tenant_id})");

        // Ensure tenant access
        if ($channel->tenant_id !== Auth::user()->tenant_id) {
            Log::warning("Unauthorized task generation attempt. User tenant: " . Auth::user()->tenant_id . ", Channel tenant: {$channel->tenant_id}");
            abort(403);
        }

        try {
            \App\Jobs\GenerateAutomatedTasksForChannel::dispatch($channel);
            Log::info("Job GenerateAutomatedTasksForChannel dispatched successfully.");
            return back()->with('success', 'Task generation started.');
        } catch (\Exception $e) {
            Log::error("Failed to dispatch task generation job: " . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to start task generation.']);
        }
    }
}
