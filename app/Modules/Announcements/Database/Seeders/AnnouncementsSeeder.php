<?php

namespace App\Modules\Announcements\Database\Seeders;

use App\Modules\Announcements\Models\Announcement;
use App\Modules\Core\Models\Church;
use Illuminate\Database\Seeder;

class AnnouncementsSeeder extends Seeder
{
    /**
     * Sample announcements: one pinned, two regular, one expired. Skipped
     * when the branch already has announcements.
     */
    public function run(): void
    {
        // Sample content for developer machines and tests only: a real site
        // starts empty and the team fills it in.
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $church = Church::default();

        if (! $church || Announcement::query()->withoutGlobalScopes()->where('church_id', $church->id)->exists()) {
            return;
        }

        $today = Announcement::today();

        $samples = [
            ['Perubahan jam Ibadah Raya II', "Mulai bulan depan, Ibadah Raya II dimulai pukul 17.30 WIB.\nIbadah Raya I tetap pukul 08.00 WIB.", true, $today->copy()->subDays(1), null],
            ['Pendaftaran baptisan air', "Jemaat yang rindu dibaptis dapat mendaftar di sekretariat atau lewat WhatsApp sekretariat.\nKelas persiapan diadakan dua kali sebelum baptisan.", false, $today->copy()->subDays(3), $today->copy()->addDays(30)],
            ['Persembahan kasih untuk korban banjir', 'Gereja membuka persembahan kasih bagi jemaat yang terdampak banjir. Bantuan dapat diserahkan ke sekretariat.', false, $today->copy()->subDays(6), $today->copy()->addDays(14)],
            ['Libur sekretariat', 'Sekretariat tutup selama libur nasional.', false, $today->copy()->subDays(20), $today->copy()->subDays(15)],
        ];

        foreach ($samples as [$title, $body, $pinned, $publishedOn, $expiresOn]) {
            $announcement = new Announcement([
                'title' => $title,
                'body' => $body,
                'is_pinned' => $pinned,
                'is_published' => true,
                'published_on' => $publishedOn,
                'expires_on' => $expiresOn,
            ]);
            // Set by hand: DatabaseSeeder runs without model events.
            $announcement->church_id = $church->id;
            $announcement->slug = Announcement::uniqueSlug($title, $church->id);
            $announcement->save();
        }
    }
}
