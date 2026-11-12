<?php

namespace App\Modules\Devotionals\Database\Seeders;

use App\Models\User;
use App\Modules\Core\Models\Church;
use App\Modules\Devotionals\Enums\DevotionalStatus;
use App\Modules\Devotionals\Models\Devotional;
use Illuminate\Database\Seeder;

class DevotionalsSeeder extends Seeder
{
    /**
     * Dummy devotionals (PRD 4.8 "data dummy awal"): a week of published
     * ones, one scheduled for tomorrow and one draft from Pelayan. Runs after
     * CoreSeeder; skipped when the branch already has devotionals.
     */
    public function run(): void
    {
        // Sample content for developer machines and tests only: a real site
        // starts empty and the team fills it in.
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $church = Church::default();

        if (! $church || Devotional::query()->withoutGlobalScopes()->where('church_id', $church->id)->exists()) {
            return;
        }

        $admin = User::query()->where('email', 'admin@example.com')->first();
        $servant = User::query()->where('email', 'pelayan@example.com')->first();
        $today = Devotional::today();

        foreach ($this->samples() as $index => [$title, $reference, $verse, $body]) {
            // Index 0 is a draft, 1 is scheduled for tomorrow, the rest are
            // published on the days before today.
            $devotional = new Devotional([
                'title' => $title,
                'scripture_ref' => $reference,
                'scripture_text' => $verse,
                'body' => $body,
                'publish_date' => $index === 0 ? $today->copy()->addDays(2) : $today->copy()->subDays($index - 2),
            ]);
            // Set by hand: DatabaseSeeder runs without model events.
            $devotional->church_id = $church->id;
            $devotional->slug = Devotional::uniqueSlug($title, $church->id);
            $devotional->created_by = ($index === 0 ? $servant : $admin)?->id;

            if ($index > 0) {
                $devotional->status = DevotionalStatus::Published;
                $devotional->published_by = $admin?->id;
                $devotional->published_at = now();
            }

            $devotional->save();
        }
    }

    /**
     * @return list<array{0: string, 1: string, 2: string, 3: string}>
     */
    private function samples(): array
    {
        return [
            ['Berjalan dalam Terang', '1 Yohanes 1:7', 'Tetapi jika kita hidup di dalam terang sama seperti Dia ada di dalam terang, maka kita beroleh persekutuan seorang dengan yang lain.',
                "Terang memperlihatkan apa adanya. Ketika kita hidup di dalam terang Tuhan, tidak ada yang perlu disembunyikan.\n\nHari ini, bawalah setiap bagian hidup Anda kepada-Nya dan nikmati persekutuan yang jujur dengan saudara seiman."],
            ['Kekuatan yang Baru', 'Yesaya 40:31', 'Tetapi orang-orang yang menanti-nantikan TUHAN mendapat kekuatan baru: mereka seumpama rajawali yang naik terbang dengan kekuatan sayapnya.',
                "Menanti Tuhan bukan berarti diam tanpa harapan. Menanti adalah percaya bahwa waktu-Nya adalah yang terbaik.\n\nSaat Anda lelah, datanglah kepada-Nya. Ia memperbarui kekuatan setiap orang yang berharap kepada-Nya."],
            ['Gembala yang Baik', 'Mazmur 23:1', 'TUHAN adalah gembalaku, takkan kekurangan aku.',
                "Daud mengenal Tuhan sebagai gembala yang memimpin, menjaga, dan mencukupkan.\n\nHari ini, serahkan langkah Anda kepada-Nya dan percayalah bahwa Ia memelihara hidup Anda, bahkan di lembah yang kelam sekalipun."],
            ['Damai Sejahtera', 'Yohanes 14:27', 'Damai sejahtera Kutinggalkan bagimu. Damai sejahtera-Ku Kuberikan kepadamu, dan apa yang Kuberikan tidak seperti yang diberikan oleh dunia kepadamu.',
                "Damai dari Yesus tidak bergantung pada keadaan. Di tengah kabar yang membuat cemas, damai-Nya menjaga hati dan pikiran kita.\n\nJangan gelisah dan gentar. Ia menyertai Anda hari ini."],
            ['Kasih yang Sabar', '1 Korintus 13:4', 'Kasih itu sabar; kasih itu murah hati; ia tidak cemburu. Ia tidak memegahkan diri dan tidak sombong.',
                "Kasih sejati terlihat dalam kesabaran sehari-hari: di rumah, di tempat kerja, di jalan.\n\nMintalah Tuhan memberi Anda kasih yang sabar bagi orang yang paling sulit Anda kasihi minggu ini."],
            ['Setia dalam Perkara Kecil', 'Lukas 16:10', 'Barangsiapa setia dalam perkara-perkara kecil, ia setia juga dalam perkara-perkara besar.',
                "Tuhan memperhatikan kesetiaan kita dalam hal-hal yang tampak kecil.\n\nPekerjaan sederhana yang dilakukan dengan setia adalah ibadah yang menyenangkan hati-Nya."],
            ['Bersukacita Senantiasa', 'Filipi 4:4', 'Bersukacitalah senantiasa dalam Tuhan! Sekali lagi kukatakan: Bersukacitalah!',
                "Paulus menulis kata-kata ini dari penjara. Sukacitanya tidak berasal dari keadaan, tetapi dari Tuhan.\n\nSebutkan tiga hal yang Anda syukuri hari ini, dan biarkan sukacita Tuhan menjadi kekuatan Anda."],
        ];
    }
}
