<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class FieldFolder extends Model
{
    use BelongsToTenant;

    protected $fillable = ['object_id', 'name', 'order_index'];

    public function object()
    {
        return $this->belongsTo(CustomFieldObject::class, 'object_id');
    }

    public function fields()
    {
        return $this->hasMany(CustomField::class, 'folder_id');
    }
}
