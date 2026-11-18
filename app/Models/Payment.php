<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'member_id', 'amount', 'purpose',
        'razorpay_order_id', 'razorpay_payment_id', 'status',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
