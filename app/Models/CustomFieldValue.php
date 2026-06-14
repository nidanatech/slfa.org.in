<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomFieldValue extends Model
{
    protected $fillable = [
        'object_id',
        'record_id',
        'field_id',
        'value_text',
        'value_number',
        'value_date',
        'value_boolean'
    ];

    protected $casts = [
        'value_number' => 'decimal:2',
        'value_date' => 'datetime',
        'value_boolean' => 'boolean',
    ];

    public function field()
    {
        return $this->belongsTo(CustomField::class, 'field_id');
    }

    public function object()
    {
        return $this->belongsTo(CustomFieldObject::class, 'object_id');
    }
    
    // Helper to get typed value
    public function getValueAttribute()
    {
        if ($this->value_text !== null) return $this->value_text;
        if ($this->value_number !== null) return $this->value_number;
        if ($this->value_date !== null) return $this->value_date;
        if ($this->value_boolean !== null) return $this->value_boolean;
        return null;
    }
}
