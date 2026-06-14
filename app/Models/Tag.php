<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Tag extends Model
{
    use BelongsToTenant;
    protected $fillable = ['name', 'slug', 'color', 'tenant_id'];

    // Polymorphic relationships
    public function contacts()
    {
        return $this->morphedByMany(Contact::class, 'object', 'taggables', 'tag_id', 'object_id');
    }
}
