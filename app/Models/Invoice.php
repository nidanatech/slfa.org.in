<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Traits\BelongsToTenant;

class Invoice extends Model
{
    use BelongsToTenant;
    protected $fillable = [
        'tenant_id',
        'invoice_number',
        'party_id', // New
        'contact_id',
        'billing_address_id', // New
        'shipping_address_id', // New
        'company_id',
        'invoice_date',
        'due_date',
        'status',
        'currency',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'paid_amount',
        'balance_due',
        'tax_type',
        'tax_rate',
        'tax_details',
        'notes',
        'terms',
        'footer',
        'portal_token',
        'gstin_snapshot', // New
        'address_snapshot', // New
        'sent_at',
        'viewed_at',
        'paid_at',
        'cancelled_at',
        'billing_address', // Keep for backward compatibility or remove if migrated? user said "snapshot"
        'shipping_address',
        'sale_agent_id',
        'payment_mode_id',
        'opportunity_id',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_details' => 'array',
        'address_snapshot' => 'array', // New
        'sent_at' => 'datetime',
        'viewed_at' => 'datetime',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'billing_address' => 'array',
        'shipping_address' => 'array',
    ];

    public function party()
    {
        return $this->belongsTo(Party::class);
    }

    public function billingAddress()
    {
        return $this->belongsTo(Address::class, 'billing_address_id');
    }

    public function shippingAddress()
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    // Relationships
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function saleAgent()
    {
        return $this->belongsTo(User::class, 'sale_agent_id');
    }

    public function paymentMode()
    {
        return $this->belongsTo(PaymentMode::class);
    }

    // Scopes
    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'paid')
            ->where('status', '!=', 'cancelled')
            ->where('due_date', '<', now());
    }

    public function scopeUnpaid($query)
    {
        return $query->where('balance_due', '>', 0)
            ->where('status', '!=', 'cancelled');
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Methods
    public function calculateTotals()
    {
        $this->subtotal = $this->items->sum('subtotal');
        $this->tax_amount = $this->items->sum('tax_amount');
        $this->total_amount = $this->subtotal + $this->tax_amount - $this->discount_amount;
        $this->balance_due = $this->total_amount - $this->paid_amount;
        $this->save();
    }

    public function markAsPaid()
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
            'paid_amount' => $this->total_amount,
            'balance_due' => 0,
        ]);
    }

    public function send()
    {
        if (!$this->portal_token) {
            $this->portal_token = Str::random(64);
        }

        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function cancel()
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    public function markAsViewed()
    {
        if (!$this->viewed_at) {
            $this->update([
                'status' => 'viewed',
                'viewed_at' => now(),
            ]);
        }
    }

    public function recordPayment($amount)
    {
        $this->paid_amount += $amount;
        $this->balance_due = $this->total_amount - $this->paid_amount;

        if ($this->balance_due <= 0) {
            $this->status = 'paid';
            $this->paid_at = now();
        } elseif ($this->paid_amount > 0) {
            $this->status = 'partial';
        }

        $this->save();
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (!$invoice->invoice_number) {
                $invoice->invoice_number = static::generateInvoiceNumber($invoice->tenant_id);
            }
        });
    }

    public static function generateInvoiceNumber($tenantId)
    {
        $tenant = Tenant::find($tenantId);
        $settings = $tenant->settings['accounting'] ?? [];
        
        $prefix = $settings['invoice_prefix'] ?? 'INV-';
        $format = $settings['invoice_number_format'] ?? '{PREFIX}{YEAR}-{NUMBER:5}';
        $startNumber = $settings['invoice_start_number'] ?? 1;

        // Get the last invoice number for this tenant
        $lastInvoice = static::where('tenant_id', $tenantId)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastInvoice ? ($lastInvoice->id + 1) : $startNumber;

        // Replace tokens
        $number = str_replace('{PREFIX}', $prefix, $format);
        $number = str_replace('{YEAR}', date('Y'), $number);
        $number = str_replace('{YY}', date('y'), $number);
        $number = str_replace('{MONTH}', date('m'), $number);

        // Handle {NUMBER:X} token with padding
        if (preg_match('/{NUMBER:(\d+)}/', $number, $matches)) {
            $padding = (int)$matches[1];
            $number = preg_replace('/{NUMBER:\d+}/', str_pad($nextNumber, $padding, '0', STR_PAD_LEFT), $number);
        } else {
            $number = str_replace('{NUMBER}', $nextNumber, $number);
        }

        return $number;
    }
}
