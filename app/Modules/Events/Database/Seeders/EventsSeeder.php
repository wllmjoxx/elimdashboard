<?php

namespace App\Modules\Events\Database\Seeders;

use App\Modules\Core\Models\Church;
use App\Modules\Events\Models\Event;
use Illuminate\Database\Seeder;

class EventsSeeder extends Seeder
{
    /**
     * Sample events around today: one running now, three coming and one past,
     * so every part of /kegiatan has something. Skipped when the branch
     * already has events.
     */
    public function run(): void
    {
        $church = Church::default();

        if (! $church || Event::query()->withoutGlobalScopes()->where('church_id', $church->id)->exists()) {
            return;
        }

        $today = Event::now()->startOfDay();

        $samples = [
            ['Pekan Doa dan Puasa', 'Satu minggu berdoa dan berpuasa bersama untuk gereja dan kota Ciputat.', 'Gedung GGP Elim Ciputat', $today->copy()->subDays(2)->setTime(5, 0), $today->copy()->addDays(4)->setTime(21, 0)],
            ['Kebaktian Kebangunan Rohani', 'Ibadah malam dengan pujian dan firman. Ajak keluarga dan sahabat Anda.', 'Gedung GGP Elim Ciputat', $today->copy()->addDays(9)->setTime(18, 30), $today->copy()->addDays(9)->setTime(21, 0)],
            ['Retret Youth Elim', 'Tiga hari bersama Youth dan Pemuda/i: firman, permainan, dan persekutuan.', 'Puncak, Bogor', $today->copy()->addDays(20)->setTime(7, 0), $today->copy()->addDays(22)->setTime(15, 0)],
            ['Natal Sekolah Minggu', 'Drama dan pujian anak-anak Elim Kids. Orang tua diundang hadir.', 'Gedung GGP Elim Ciputat', $today->copy()->addDays(35)->setTime(9, 0), null],
            ['Bakti Sosial Usia Emas', 'Pemeriksaan kesehatan gratis dan makan bersama untuk jemaat lansia.', 'Gedung GGP Elim Ciputat', $today->copy()->subDays(14)->setTime(9, 0), $today->copy()->subDays(14)->setTime(13, 0)],
        ];

        foreach ($samples as [$title, $summary, $location, $start, $end]) {
            $event = new Event([
                'title' => $title,
                'summary' => $summary,
                'body' => $summary,
                'location' => $location,
                'starts_at' => $start,
                'ends_at' => $end,
            ]);
            // Set by hand: DatabaseSeeder runs without model events.
            $event->church_id = $church->id;
            $event->slug = Event::uniqueSlug($title, $church->id);
            $event->is_published = true;
            $event->save();
        }
    }
}
