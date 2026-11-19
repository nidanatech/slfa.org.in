<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function entries(): array
    {
        return [
            ['Fourth Annual General Meeting', 'AGM', '2023-09-30', 'The fourth Annual General Meeting of the Sri Lankan Friendship Association of South India was held on 30 September 2023.', '/images/cta/gallery4.jpg'],
            ['Third Annual General Meeting', 'AGM', '2022-09-24', 'The third Annual General Meeting of the Sri Lankan Friendship Association of South India was held on 24 September 2022.', '/images/cta/gallery4.jpg'],
            ['Second Annual General Meeting', 'AGM', '2021-12-11', 'The second Annual General Meeting of the Sri Lankan Friendship Association of South India was held on 11 December 2021.', '/images/cta/gallery4.jpg'],
            ['Annual Get Together at GRT Grand', 'Get-Together', '2019-11-16', 'Members gathered for dinner on 16 November 2019, from 6:00 p.m. to 11:00 p.m., at the Marigold Hall, Hotel GRT Grand, T. Nagar, Chennai.', '/images/cta/gallery27.jpg'],
            ['Free Health Check-up at Dr. Rela Institute', 'Health Checkup', '2019-03-23', 'A free health check-up camp for association members was held on 23 March 2019 from 10:00 a.m. at Dr. Rela Institute & Medical Centre, 7, CLC Works Road, Chromepet, Chennai 600044.', null],
            ['Annual Get Together at Accord Metropolitan', 'Get-Together', '2018-12-15', 'Members met for dinner on 15 December 2018, from 7:00 p.m. to 11:00 p.m., at Hotel Accord Metropolitan, G. N. Chetty Road, T. Nagar, Chennai.', '/images/cta/gallery15.jpg'],
            ['Annual Get Together in March 2018', 'Get-Together', '2018-03-17', 'The association held a dinner gathering on 17 March 2018, from 7:00 p.m. to 11:00 p.m., at Hotel Accord Metropolitan, T. Nagar, Chennai.', '/images/cta/gallery15.jpg'],
            ['Sri Lankan Get Together 2016', 'Get-Together', '2016-04-09', 'A Sri Lankan get together was held on 9 April 2016.', '/images/cta/gallery27.jpg'],
            ['Medical and Eye Camp in Devaneri', 'Medical Camp', '2015-12-26', 'To commemorate Tsunami Day, a special medical and eye camp was conducted on 26 December 2015 at Devaneri Village, Mahabalipuram.', null],
        ];
    }

    public function up(): void
    {
        foreach ($this->entries() as [$title, $category, $date, $body, $image]) {
            $publishedAt = Carbon::parse($date.' 12:00:00', 'Asia/Kolkata')->utc();

            DB::table('news_posts')->updateOrInsert(
                ['title' => $title, 'published_at' => $publishedAt],
                [
                    'body' => $body,
                    'category' => $category,
                    'cover_image_path' => $image,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        foreach ($this->entries() as [$title, , $date]) {
            DB::table('news_posts')
                ->where('title', $title)
                ->where('published_at', Carbon::parse($date.' 12:00:00', 'Asia/Kolkata')->utc())
                ->delete();
        }
    }
};
