<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectStakeholder extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'opportunity_id',
        'contact_id',
        'role',
        'role_type',
        'influence',
        'interest',
        'strategy',
        'classification',
        'notes'
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class, 'opportunity_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
