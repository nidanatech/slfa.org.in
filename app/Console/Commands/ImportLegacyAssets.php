<?php

namespace App\Console\Commands;

use App\Models\CommitteeMember;
use App\Models\Event;
use App\Models\GalleryAlbum;
use App\Models\GalleryMedia;
use App\Models\NewsPost;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ImportLegacyAssets extends Command
{
    protected $signature = 'slfa:import-legacy-assets';
    protected $description = 'Import legacy assets and seed initial data from slfa.org.in';

    public function handle()
    {
        $this->info('Starting legacy asset import...');

        // Base URL for old site assets
        $baseUrl = 'https://slfa.org.in/assets/img/';
        
        $this->info('1. Seeding Committee Members...');
        $this->seedCommitteeMembers($baseUrl);

        $this->info('2. Seeding Gallery...');
        $this->seedGallery($baseUrl);

        $this->info('3. Seeding Events...');
        $this->seedEvents();

        $this->info('4. Seeding News Posts...');
        $this->seedNewsPosts();

        $this->info('5. Downloading Logos and Form Images...');
        $this->downloadMiscAssets($baseUrl);

        $this->info('Legacy asset import completed!');
    }

    private function downloadFile($url, $path)
    {
        try {
            $response = Http::timeout(30)->get($url);
            if ($response->successful()) {
                Storage::disk('public')->put($path, $response->body());
                return true;
            }
        } catch (\Exception $e) {
            $this->error("Failed to download: $url - " . $e->getMessage());
        }
        return false;
    }

    private function seedCommitteeMembers($baseUrl)
    {
        CommitteeMember::truncate();

        $present = [
            ['name' => 'Bose Dharmalingam', 'role' => 'President', 'file' => 'home/team/present/dharmalingam.jpg'],
            ['name' => 'Syed Mohamed Aminuddin', 'role' => 'Secretary', 'file' => 'home/team/present/aminuddin.jpg'],
            ['name' => 'Kumudhini J. Yogaratnam', 'role' => 'Treasurer', 'file' => 'home/team/present/yogaratnam.jpg'],
        ];

        foreach ($present as $index => $p) {
            $path = 'legacy/' . $p['file'];
            if ($this->downloadFile($baseUrl . $p['file'], $path)) {
                CommitteeMember::create([
                    'name' => $p['name'],
                    'role' => $p['role'],
                    'tier' => 'present',
                    'photo_path' => $path,
                    'display_order' => $index,
                ]);
            }
        }

        $executive = [
            ['name' => 'K.S.Sri Giriprasath', 'role' => 'Executive Member', 'file' => 'home/team/committee/prasath.jpg'],
            ['name' => 'Dr. A. Ravisankar', 'role' => 'Executive Member', 'file' => 'home/team/committee/arumugam.jpg'], // note filename arumugam.jpg might not match dr ravisankar but following spec
            ['name' => 'Richard Allen Vincent', 'role' => 'Executive Member', 'file' => 'home/team/committee/vincent.jpg'],
            ['name' => 'S. Prakash', 'role' => 'Executive Member', 'file' => 'home/team/committee/prakash.jpg'],
        ];

        foreach ($executive as $index => $e) {
            $path = 'legacy/' . $e['file'];
            if ($this->downloadFile($baseUrl . $e['file'], $path)) {
                CommitteeMember::create([
                    'name' => $e['name'],
                    'role' => $e['role'],
                    'tier' => 'executive',
                    'photo_path' => $path,
                    'display_order' => $index,
                ]);
            }
        }
    }

    private function seedGallery($baseUrl)
    {
        GalleryAlbum::truncate();
        GalleryMedia::truncate();

        $album = GalleryAlbum::create([
            'title' => 'Legacy Gallery',
            'cover_image_path' => 'legacy/gallery/gallery1.jpg',
        ]);

        for ($i = 1; $i <= 41; $i++) {
            $filename = "gallery{$i}.jpg";
            $path = "legacy/gallery/$filename";
            if ($this->downloadFile($baseUrl . 'gallery/' . $filename, $path)) {
                $album->media()->create([
                    'path' => $path,
                    'type' => 'image',
                    'sort_order' => $i,
                ]);
            }
        }

        $videoPath = 'legacy/gallery/galleryvideo.mp4';
        if ($this->downloadFile($baseUrl . 'gallery/galleryvideo.mp4', $videoPath)) {
            $album->media()->create([
                'path' => $videoPath,
                'type' => 'video',
                'sort_order' => 42,
            ]);
        }
    }

    private function seedEvents()
    {
        Event::truncate();

        $events = [
            ['title' => 'Annual General Meeting 2023', 'event_type' => 'AGM', 'start_at' => '2023-01-01 10:00:00'],
            ['title' => 'Annual General Meeting 2022', 'event_type' => 'AGM', 'start_at' => '2022-01-01 10:00:00'],
            ['title' => 'Annual General Meeting 2021', 'event_type' => 'AGM', 'start_at' => '2021-01-01 10:00:00'],
            ['title' => 'Annual General Meeting', 'event_type' => 'AGM', 'start_at' => '2020-01-01 10:00:00'],
            ['title' => 'Get-Together 2019', 'event_type' => 'Get-Together', 'start_at' => '2019-01-01 10:00:00'],
            ['title' => 'Get-Together 2018 (2)', 'event_type' => 'Get-Together', 'start_at' => '2018-06-01 10:00:00'],
            ['title' => 'Get-Together 2018 (1)', 'event_type' => 'Get-Together', 'start_at' => '2018-01-01 10:00:00'],
            ['title' => 'Get-Together 2016', 'event_type' => 'Get-Together', 'start_at' => '2016-01-01 10:00:00'],
            ['title' => 'Tsunami Day Medical/Eye Camp', 'event_type' => 'Medical Camp', 'location' => 'Devaneri Village', 'start_at' => '2015-12-01 10:00:00'],
            ['title' => 'Free Health Checkup', 'event_type' => 'Health Checkup', 'location' => 'Dr. Rela Institute', 'start_at' => '2019-03-01 10:00:00'],
        ];

        foreach ($events as $e) {
            Event::create($e);
        }
    }

    private function seedNewsPosts()
    {
        NewsPost::truncate();

        NewsPost::create([
            'title' => 'Upcoming AGM & Activities',
            'body' => 'Details regarding our upcoming Annual General Meeting and other activities will be posted here.',
            'category' => 'AGM',
            'published_at' => now(),
        ]);
        
        NewsPost::create([
            'title' => 'Medical Camp Success',
            'body' => 'The Tsunami Day Medical/Eye Camp at Devaneri Village was a great success, serving many in need.',
            'category' => 'Medical Camp',
            'published_at' => '2015-12-05 10:00:00',
        ]);
    }

    private function downloadMiscAssets($baseUrl)
    {
        $misc = [
            'logo.png',
            'footer.png',
            'srilankan-friendship-logo.png',
            'contact/shape-2.png',
            'Business-Membership-form.png',
            'Individual-Membership-form.png',
            'CCF06102023.pdf',
            'membership_form.pdf',
        ];

        foreach ($misc as $file) {
            $this->downloadFile($baseUrl . $file, 'legacy/' . $file);
        }
    }
}
