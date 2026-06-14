<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'name',
        'file_path',
        'type', // e.g., 'contract', 'license', 'nda', 'other'
        'status', // 'pending', 'verified', 'rejected'
        'verification_note',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
