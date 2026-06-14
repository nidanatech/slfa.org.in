<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Activity extends Model
{
    use BelongsToTenant;
    
    protected $table = 'activity_index';
    
    protected $fillable = [
        'tenant_id', 'contact_id', 'activity_type', 'source_table', 'source_id', 'summary', 'actor_id'
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    // Since we are using strings in source_table, we cannot use morphTo directly in standard way
    // unless we alias the table names to models in AppServiceProvider or just manual finding.
    // But let's try to map it if possible.
    // If the user intends source_table to be the model name, we can use it.
    // If it is actual table name, we might need a helper.
    // Given the request, let's assume polymorphic handling will be done manually or via a helper if needed.
    // For now, no direct morph relation unless we know source_table contains class name.

}
