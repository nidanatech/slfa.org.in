<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PartyContact extends Pivot
{
    protected $table = 'party_contacts';

    public $incrementing = true; // Since we added an ID column
}
