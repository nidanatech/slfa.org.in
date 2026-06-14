<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomFieldObject extends Model
{
    protected $fillable = ['name'];

    public function folders()
    {
        return $this->hasMany(FieldFolder::class, 'object_id');
    }

    public function fields()
    {
        return $this->hasMany(CustomField::class, 'object_id');
    }
}
