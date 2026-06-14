<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PartyAddress extends Pivot
{
    protected $table = 'party_addresses';

    public $incrementing = true; // Since we added an ID column
}
