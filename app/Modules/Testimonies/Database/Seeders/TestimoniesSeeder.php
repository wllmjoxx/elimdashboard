<?php

namespace App\Modules\Testimonies\Database\Seeders;

use App\Models\User;
use App\Modules\Core\Models\Church;
use App\Modules\Testimonies\Enums\TestimonyCategory;
use App\Modules\Testimonies\Enums\TestimonyStatus;
use App\Modules\Testimonies\Models\Testimony;
use Illuminate\Database\Seeder;

class TestimoniesSeeder extends Seeder
{
    /**
     * Sample testimonies: three approved (two featured on Home) and two
     * waiting for moderation. Skipped when the branch already has some.
     */
    public function run(): void
    {
        // Sample content for developer machines and tests only: a real site
        // starts empty and the team fills it in.
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $church = Church::default();

        if (! $church || Testimony::query()->withoutGlobalScopes()->where('church_id', $church->id)->exists()) {
            return;
        }

        $admin = User::query()->where('email', 'admin@example.com')->first();

        $samples = [
            ['Tuhan memulihkan kesehatan ayah', TestimonyCategory::Healing, 'Ibu R.', TestimonyStatus::Approved, true,
                'Bulan lalu ayah saya dirawat karena serangan jantung. Jemaat dan Tim Doa terus mendoakan. Puji Tuhan, ayah pulih lebih cepat dari perkiraan dokter dan sekarang sudah bisa beribadah lagi bersama kami.'],
            ['Pekerjaan di waktu yang tepat', TestimonyCategory::Provision, null, TestimonyStatus::Approved, true,
                'Setelah delapan bulan mencari pekerjaan, saya hampir menyerah. Lewat renungan harian saya diingatkan untuk tetap percaya. Minggu lalu saya diterima bekerja, tepat sebelum tabungan kami habis.'],
            ['Keluarga kami kembali utuh', TestimonyCategory::Family, 'Keluarga S.', TestimonyStatus::Approved, false,
                'Hubungan kami sempat retak bertahun-tahun. Melalui ibadah keluarga dan pendampingan gembala, kami belajar saling mengampuni. Sekarang kami bisa duduk bersama lagi setiap Minggu.'],
            ['Doa untuk anak kami terjawab', TestimonyCategory::AnsweredPrayer, null, TestimonyStatus::Pending, false,
                'Anak kami lulus ujian masuk sekolah yang ia doakan sejak lama. Terima kasih untuk Sekolah Minggu yang selalu mendukung.'],
            ['Damai di tengah kehilangan', TestimonyCategory::Other, 'Om T.', TestimonyStatus::Pending, false,
                'Setelah istri saya meninggal, saya merasa sendirian. Kunjungan jemaat Usia Emas memberi saya kekuatan untuk melangkah lagi.'],
        ];

        foreach ($samples as $index => [$title, $category, $name, $status, $featured, $body]) {
            $testimony = new Testimony([
                'title' => $title,
                'body' => $body,
                'category' => $category,
                'display_name' => $name,
            ]);
            // Set by hand: DatabaseSeeder runs without model events.
            $testimony->church_id = $church->id;
            $testimony->status = $status;
            $testimony->is_featured = $featured;

            if ($status === TestimonyStatus::Approved) {
                $testimony->moderated_by = $admin?->id;
                $testimony->moderated_at = now()->subDays(3 - $index);
            }

            $testimony->save();
        }
    }
}
