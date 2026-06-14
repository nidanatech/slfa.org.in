<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Contact;
use App\Models\Company;
use App\Models\Party;
use App\Models\Address;
use App\Models\PartyAddress;
use App\Models\PartyContact;
use Illuminate\Support\Facades\DB;

class MigrateContactsToParties extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crm:migrate-parties';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing Companies and Contacts to Parties';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting migration to Parties...');

        DB::transaction(function () {
            // 1. Migrate Companies
            $companies = Company::all();
            $this->info("Found {$companies->count()} companies.");

            foreach ($companies as $company) {
                $this->info("Migrating Company: {$company->name}");

                $party = Party::firstOrCreate(
                    [
                        'company_id' => $company->id,
                        'tenant_id' => $company->tenant_id
                    ],
                    [
                        'party_type' => 'company',
                        'name' => $company->name,
                        'currency' => 'USD', // Default
                    ]
                );

                // Create Address if exists
                if ($company->address || $company->city) {
                    $address = Address::create([
                        'tenant_id' => $company->tenant_id,
                        'line1' => $company->address ?? '',
                        'city' => $company->city ?? '',
                        'state' => $company->state ?? '',
                        'postal_code' => $company->zip ?? '',
                        'country' => $company->country ?? 'USA', // Default
                    ]);

                    // Attach as Billing and Shipping
                    PartyAddress::create([
                        'party_id' => $party->id,
                        'address_id' => $address->id,
                        'address_type' => 'billing',
                        'is_default' => true,
                    ]);
                    PartyAddress::create([
                        'party_id' => $party->id,
                        'address_id' => $address->id,
                        'address_type' => 'shipping',
                        'is_default' => true,
                    ]);
                }
            }

            // 2. Migrate Contacts
            $contacts = Contact::all();
            $this->info("Found {$contacts->count()} contacts.");

            foreach ($contacts as $contact) {
                if ($contact->company_id) {
                    // Link to Company Party
                    $party = Party::where('company_id', $contact->company_id)->first();
                    if ($party) {
                        $this->info("Linking Contact {$contact->name} to Company Party {$party->name}");
                        $party->contacts()->syncWithoutDetaching([
                            $contact->id => ['is_primary' => false, 'receives_invoice' => true]
                        ]);
                    }
                } else {
                    // Create Individual Party
                    // Check if Party already exists for this contact (avoid dupes if re-run)
                    // We don't have a direct link back from Contact to Party easily unless we store contact_id on Party (not in schema)
                    // Or we check if this contact is already linked to a Party of type Individual
                    
                    $existingLink = PartyContact::where('contact_id', $contact->id)->exists();
                    if (!$existingLink) {
                         $this->info("Creating Individual Party for Contact: {$contact->name}");
                         
                         $party = Party::create([
                             'tenant_id' => $contact->tenant_id,
                             'party_type' => 'individual',
                             'name' => $contact->name,
                             'currency' => 'USD',
                         ]);

                         $party->contacts()->attach($contact->id, ['is_primary' => true, 'receives_invoice' => true]);
                         
                         // Check for address on contact (even if not in fillable, might be accessible)
                         if (!empty($contact->address) || !empty($contact->city)) {
                             $address = Address::create([
                                'tenant_id' => $contact->tenant_id,
                                'line1' => $contact->address ?? '',
                                'city' => $contact->city ?? '',
                                'state' => $contact->state ?? '',
                                'postal_code' => $contact->zip ?? '', // Assuming zip might exist
                                'country' => $contact->country ?? 'USA',
                            ]);
                            
                            PartyAddress::create([
                                'party_id' => $party->id,
                                'address_id' => $address->id,
                                'address_type' => 'billing',
                                'is_default' => true,
                            ]);
                             PartyAddress::create([
                                'party_id' => $party->id,
                                'address_id' => $address->id,
                                'address_type' => 'shipping',
                                'is_default' => true,
                            ]);
                         }
                    }
                }
            }
        });

        $this->info('Migration completed successfully.');
    }
}
