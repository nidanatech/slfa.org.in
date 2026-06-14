<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class CustomField extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'object_id',
        'folder_id',
        'name',
        'key',
        'field_type',
        'ui_type',
        'options_json',
        'is_required',
        'is_unique',
        'is_system',
        'is_deleted'
    ];

    protected $casts = [
        'options_json' => 'array',
        'is_required' => 'boolean',
        'is_unique' => 'boolean',
        'is_system' => 'boolean',
        'is_deleted' => 'boolean',
    ];

    public function object()
    {
        return $this->belongsTo(CustomFieldObject::class, 'object_id');
    }

    public function folder()
    {
        return $this->belongsTo(FieldFolder::class, 'folder_id');
    }

    public function values()
    {
        return $this->hasMany(CustomFieldValue::class, 'field_id');
    }
}
