<?php

namespace App\Modules\Prayer\Database\Seeders;

use App\Models\User;
use App\Modules\Core\Models\Church;
use App\Modules\Prayer\Enums\PrayerCategory;
use App\Modules\Prayer\Enums\PrayerStatus;
use App\Modules\Prayer\Models\PrayerRequest;
use Illuminate\Database\Seeder;

class PrayerSeeder extends Seeder
{
    /**
     * Dummy prayer requests for this week and last week, so the Tim Doa panel
     * has something to show. Runs after CoreSeeder; skipped when the branch
     * already has requests.
     */
    public function run(): void
    {
        $church = Church::default();

        if (! $church || PrayerRequest::query()->withoutGlobalScopes()->where('church_id', $church->id)->exists()) {
            return;
        }

        $member = User::query()->where('email', 'jemaat@example.com')->first();
        $thisWeek = PrayerRequest::currentWeekStart();

        $samples = [
            [$member?->name ?? 'Jemaat Elim', 34, PrayerCategory::Health, 'Mohon doa untuk kesembuhan ibu saya yang sedang dirawat.', $member?->id, $thisWeek, PrayerStatus::New],
            ['Andreas Wijaya', 41, PrayerCategory::Work, 'Doakan pekerjaan baru yang sedang saya lamar.', null, $thisWeek, PrayerStatus::New],
            ['Ruth Simanjuntak', 17, PrayerCategory::Study, 'Mohon doa untuk ujian sekolah minggu depan.', null, $thisWeek, PrayerStatus::Prayed],
            ['Samuel Tan', 52, PrayerCategory::Family, 'Doakan pemulihan hubungan keluarga kami.', null, $thisWeek->copy()->subWeek(), PrayerStatus::Answered],
        ];

        foreach ($samples as [$name, $age, $category, $body, $userId, $week, $status]) {
            $prayer = new PrayerRequest([
                'full_name' => $name,
                'age' => $age,
                'phone' => '0812 0000 0000',
                'category' => $category,
                'body' => $body,
            ]);
            // Set by hand: DatabaseSeeder runs without model events.
            $prayer->church_id = $church->id;
            $prayer->user_id = $userId;
            $prayer->week_start = $week;
            $prayer->status = $status;
            $prayer->save();
        }
    }
}
