<?php

namespace App\Observers;

use App\Models\Contact;
use App\Models\Party;
use App\Models\Address;
use App\Models\PartyAddress;
use App\Models\PartyContact;

class ContactObserver
{
    /**
     * Handle the Contact "created" event.
     */
    public function created(Contact $contact): void
    {
        // 1. Log Activity
        \App\Models\Activity::create([
            'tenant_id' => $contact->tenant_id,
            'contact_id' => $contact->id,
            'activity_type' => 'created',
            'source_table' => 'contacts',
            'source_id' => $contact->id,
            'summary' => 'Contact created',
            'actor_id' => auth()->id() ?? null,
        ]);
    }

    /**
     * Handle the Contact "updated" event.
     */
    public function updated(Contact $contact): void
    {
        // Sync company_id to all related opportunities when contact's company changes
        if ($contact->isDirty('company_id')) {
            \App\Models\Opportunity::where('contact_id', $contact->id)
                ->update(['company_id' => $contact->company_id]);
        }
        
        // Sync Party Name if Individual
        if ($contact->isDirty('name') && !$contact->company_id) {
             // Find the individual party derived from this contact
             // We don't have direct link, so we check PartyContact where is_primary=true? 
             // Or find party where party_type=individual and linked to this contact
             
             $party = Party::whereHas('contacts', function($q) use ($contact) {
                 $q->where('contact_id', $contact->id);
             })->where('party_type', 'individual')->first();
             
             if ($party) {
                 $party->update(['name' => $contact->name]);
             }
        }
    }

    /**
     * Handle the Contact "deleted" event.
     */
    public function deleted(Contact $contact): void
    {
        //
    }

    /**
     * Handle the Contact "restored" event.
     */
    public function restored(Contact $contact): void
    {
        //
    }

    /**
     * Handle the Contact "force deleted" event.
     */
    public function forceDeleted(Contact $contact): void
    {
        //
    }
}
