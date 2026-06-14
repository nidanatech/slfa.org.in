<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToTenant;

class Payment extends Model
{
    use BelongsToTenant;
    protected $fillable = [
        'tenant_id',
        'payment_number',
        'invoice_id',
        'contact_id',
        'product_id',
        'product_variation_id',
        'event_id',
        'opportunity_id',
        'payment_date',
        'amount',
        'currency',
        'payment_method',
        'payment_method_details',
        'transaction_id',
        'reference_number',
        'type',
        'status',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'payment_method_details' => 'array',
    ];

    // Relationships
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    // Scopes
    public function scopeReceived($query)
    {
        return $query->where('type', 'received');
    }

    public function scopeSent($query)
    {
        return $query->where('type', 'sent');
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    // Methods
    public function applyToInvoice()
    {
        if ($this->invoice_id && $this->status === 'completed') {
            $this->invoice->recordPayment($this->amount);
        }
    }

    public function refund()
    {
        $this->update(['status' => 'refunded']);
        
        if ($this->invoice_id) {
            $this->invoice->recordPayment(-$this->amount);
        }
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (!$payment->payment_number) {
                $payment->payment_number = static::generatePaymentNumber($payment->tenant_id);
            }
        });

        static::created(function ($payment) {
            if ($payment->invoice_id && $payment->status === 'completed') {
                $payment->applyToInvoice();
            }
        });
    }

    public static function generatePaymentNumber($tenantId)
    {
        $tenant = Tenant::find($tenantId);
        $settings = $tenant->settings['accounting'] ?? [];
        
        $prefix = $settings['payment_prefix'] ?? 'PAY-';
        
        $lastPayment = static::where('tenant_id', $tenantId)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastPayment ? ($lastPayment->id + 1) : 1;

        return $prefix . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }
}
