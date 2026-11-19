<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const TITLE = '7th Annual Gala Cocktail Dinner 2026';

    public function up(): void
    {
        if (DB::table('events')->where('title', self::TITLE)->exists()) {
            return;
        }

        $eventId = DB::table('events')->insertGetId([
            'title' => self::TITLE,
            'description' => 'An evening of friendship, music and celebration with a live performance by Xpressions (Sri Lanka). Guest arrival and registration begin at 6:30 p.m.; the gala begins at 7:00 p.m. Dinner follows at 8:30 p.m.',
            'event_type' => 'Other',
            'location' => 'The Leela Palace, Royal Ballroom, MRC Nagar, Chennai 600028',
            'venue_name' => 'The Leela Palace, Royal Ballroom',
            'venue_address' => 'MRC Nagar, Chennai 600028',
            'timezone' => 'Asia/Kolkata',
            'start_at' => Carbon::parse('2026-11-28 19:00', 'Asia/Kolkata')->utc(),
            'end_at' => null,
            'dress_code' => 'Smart casual',
            'adult_price' => 4000,
            'child_price' => 2500,
            'child_age_range' => '5–10 years',
            'contact_details' => 'Bose: +91 98840 88808; Amin: +91 98844 13069',
            'cover_image_path' => null,
            'rsvp_enabled' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $items = [
            ['18:30', 'Guest arrival and registration', null],
            ['19:00', 'Lighting of the oil lamp', null],
            ['19:05', 'Indian and Sri Lankan national anthems', null],
            ['19:15', 'Welcome address and SLFA activities 2025–26', 'Mr. S. M. Aminuddin, Secretary, SLFA'],
            ['19:25', "President’s address: The Way Forward", 'Mr. Bose Dharmalingam, President, SLFA'],
            ['19:30', 'Introduction of the SLFA Ladies Wing', 'Mrs. Aminuddin'],
            ['19:35', 'Address by the guest of honour', 'Mr. Abbas Ali Sethwala, Global CEO, Expo Freight Ltd, Colombo, Sri Lanka'],
            ['19:40', 'Address by the chief guest', 'Dr. Sandeep Rai Rathore, IPS, Director General of Police and Chief Vigilance Officer, TANGEDCO, Tamil Nadu'],
            ['19:50', 'Vote of thanks', 'Mrs. Sooriyakumary, Vice President, SLFA'],
            ['20:00', 'Cocktails and live performance', 'Xpressions (Sri Lanka)'],
            ['20:30', 'Gala dinner', null],
        ];

        foreach ($items as $position => [$time, $title, $details]) {
            DB::table('event_programme_items')->insert([
                'event_id' => $eventId,
                'time' => $time,
                'title' => $title,
                'details' => $details,
                'sort_order' => $position,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // The flyer shows these marks but gives no verified website addresses.
        foreach (['SriLankan Airlines', 'EFL', 'Ideal', 'Apollo Hospitals', 'Bank of Ceylon'] as $position => $name) {
            DB::table('event_sponsors')->insert([
                'event_id' => $eventId,
                'name' => $name,
                'sort_order' => $position,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Keep editorial content on rollback: an administrator may have edited
        // this event, or it may have existed before the import ran.
    }
};
