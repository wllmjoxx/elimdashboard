<?php

namespace App\Modules\Gallery\Database\Seeders;

use App\Modules\Core\Models\Church;
use App\Modules\Gallery\Models\Album;
use Illuminate\Database\Seeder;

class GallerySeeder extends Seeder
{
    /**
     * Empty sample albums named after the church's activities; photos are
     * uploaded from the admin panel (PRD 4.8). Skipped when albums exist.
     */
    public function run(): void
    {
        // Sample content for developer machines and tests only: a real site
        // starts empty and the team fills it in.
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $church = Church::default();

        if (! $church || Album::query()->withoutGlobalScopes()->where('church_id', $church->id)->exists()) {
            return;
        }

        $albums = [
            ['Ibadah Raya', 'Momen ibadah raya Minggu pagi dan sore.'],
            ['Sekolah Minggu', 'Kegiatan anak-anak Sekolah Minggu.'],
            ['Youth & Pemuda/i', 'Persekutuan dan kegiatan kaum muda.'],
            ['Menara Doa', 'Persekutuan doa Selasa dan Sabtu.'],
        ];

        foreach ($albums as $index => [$title, $description]) {
            $album = new Album(['title' => $title, 'description' => $description]);
            // Set by hand: DatabaseSeeder runs without model events.
            $album->church_id = $church->id;
            $album->slug = Album::uniqueSlug($title, $church->id);
            $album->event_date = now()->subWeeks($index)->startOfWeek()->subDay();
            $album->save();
        }
    }
}
