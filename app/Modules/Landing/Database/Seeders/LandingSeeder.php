<?php

namespace App\Modules\Landing\Database\Seeders;

use App\Modules\Landing\Models\ServiceSchedule;
use App\Modules\Landing\Models\SiteSection;
use Illuminate\Database\Seeder;

class LandingSeeder extends Seeder
{
    /**
     * Initial landing page content taken from ggpelim.com via the PRD
     * (section 4.3). Everything here becomes editable by Admin later.
     * Safe to run more than once.
     */
    public function run(): void
    {
        foreach (array_values($this->sections()) as $order => [$key, $content]) {
            $attributes = ['church_id' => null, 'key' => $key];
            $values = ['content' => $content, 'is_active' => true, 'sort_order' => ($order + 1) * 10];

            // Sections edited by Admin must survive a re-run of the seeder.
            in_array($key, [...SiteSection::SEGMENT_KEYS, 'gallery'], true)
                ? SiteSection::query()->firstOrCreate($attributes, $values)
                : SiteSection::query()->updateOrCreate($attributes, $values);
        }

        if (ServiceSchedule::query()->doesntExist()) {
            foreach ($this->schedules() as $order => [$category, $name, $day, $time]) {
                ServiceSchedule::query()->create([
                    'category' => $category,
                    'name' => $name,
                    'day' => $day,
                    'start_time' => $time,
                    'sort_order' => ($order + 1) * 10,
                ]);
            }
        }
    }

    /**
     * @return list<array{0: string, 1: array<string, mixed>}>
     */
    private function sections(): array
    {
        return [
            ['hero', [
                'slides' => [
                    [
                        'eyebrow' => 'Tema',
                        'title' => 'Yesus Kristus Imam Besar Agung kita',
                        'verse' => 'Karena kita sekarang mempunyai Imam Besar Agung, yang telah melintasi semua langit, yaitu Yesus, Anak Allah, baiklah kita teguh berpegang pada pengakuan iman kita.',
                        'reference' => 'Ibrani 4:14',
                    ],
                    [
                        'eyebrow' => 'Selamat datang',
                        'title' => 'Rumah Kasih Karunia',
                        'text' => 'Gereja Gerakan Pentakosta Jemaat Elim Ciputat. Mari beribadah dan bertumbuh bersama kami.',
                    ],
                    [
                        'eyebrow' => 'Ibadah Raya',
                        'title' => 'Setiap Minggu, pukul 08.00 dan 17.00',
                        'text' => 'Kami menantikan Anda dan keluarga di GGP Elim Ciputat.',
                    ],
                ],
                'primary_cta' => ['label' => 'Lihat jadwal ibadah', 'href' => '/jadwal'],
                'secondary_cta' => ['label' => 'Kirim pokok doa', 'href' => '/doa'],
            ]],
            ['segment_1', [
                'eyebrow' => 'GGP Elim Ciputat',
                'title' => 'Rumah Kasih Karunia',
                'text' => 'Keluarga Allah di Ciputat yang rindu setiap orang mengalami kasih karunia Yesus Kristus dan bertumbuh di dalam-Nya.',
                'button_label' => 'Kenali kami',
                'button_href' => '/tentang',
                'align' => 'center',
                'image' => null,
            ]],
            ['about', [
                'title' => 'Tentang GGP Elim',
                'paragraphs' => [
                    'Gereja Gerakan Pentakosta (GGP) didirikan pada tahun 1923 di Bandung.',
                    'GGP Jemaat Elim Ciputat mulai dirintis sekitar tahun 1958, dan gedung gerejanya diresmikan sekitar tahun 1967.',
                ],
                'milestones' => [
                    ['year' => '1923', 'text' => 'Gereja Gerakan Pentakosta didirikan di Bandung'],
                    ['year' => '± 1958', 'text' => 'GGP Jemaat Elim Ciputat mulai dirintis'],
                    ['year' => '± 1967', 'text' => 'Gedung gereja diresmikan'],
                ],
            ]],
            ['mission', [
                'title' => 'Misi',
                'text' => 'Mencari dan menyelamatkan jiwa-jiwa yang terhilang berapapun harganya.',
                'verse' => 'Sebab Anak Manusia datang untuk mencari dan menyelamatkan yang hilang.',
                'reference' => 'Lukas 19:10',
            ]],
            // Placeholder until the Devotionals module publishes real devotionals.
            ['devotional', [
                'label' => 'Renungan Harian',
                'title' => 'Gembala yang Baik',
                'reference' => 'Mazmur 23:1',
                'verse' => 'TUHAN adalah gembalaku, takkan kekurangan aku.',
                'excerpt' => 'Daud mengenal Tuhan sebagai gembala yang memimpin, menjaga, dan mencukupkan. Hari ini, serahkan langkah Anda kepada-Nya dan percayalah bahwa Ia memelihara hidup Anda.',
                'is_sample' => true,
            ]],
            ['schedule', [
                'title' => 'Jadwal Kegiatan',
                'subtitle' => 'Semua waktu dalam WIB.',
            ]],
            ['segment_2', [
                'eyebrow' => 'Menara Doa',
                'title' => 'Berdoa bersama setiap Selasa dan Sabtu',
                'text' => 'Selasa pukul 19.00 dan Sabtu pukul 07.30. Bawa pokok doa Anda, atau kirimkan lewat Form Doa.',
                'button_label' => 'Kirim pokok doa',
                'button_href' => '/doa',
                'align' => 'left',
                'image' => null,
            ]],
            ['prayer', [
                'title' => 'Butuh didoakan?',
                'text' => 'Tim Doa GGP Elim mendoakan setiap pokok doa yang masuk setiap minggu.',
                'verse' => 'Dan apa saja yang kamu minta dalam doa dengan penuh kepercayaan, kamu akan menerimanya.',
                'reference' => 'Matius 21:22',
            ]],
            // Placeholder until the Gallery module provides albums and photos.
            ['gallery', [
                'title' => 'Galeri',
                'layout' => 'grid',
                'album_id' => null,
                'items' => [
                    ['caption' => 'Ibadah Raya'],
                    ['caption' => 'Sekolah Minggu'],
                    ['caption' => 'Youth & Pemuda/i'],
                    ['caption' => 'Menara Doa'],
                    ['caption' => 'Usia Emas'],
                ],
                'is_sample' => true,
            ]],
            ['segment_3', [
                'eyebrow' => 'Ibadah Komisi',
                'title' => 'Ada tempat untuk setiap usia',
                'text' => 'Sekolah Minggu, Youth dan Pemuda/i, Kaum Wanita dan Pria, hingga Usia Emas.',
                'button_label' => 'Lihat jadwal',
                'button_href' => '/jadwal',
                'align' => 'right',
                'image' => null,
            ]],
            ['testimonies', [
                'title' => 'Kesaksian',
                'text' => 'Cerita karya Tuhan dalam hidup jemaat GGP Elim.',
            ]],
            ['contact', [
                'title' => 'Alamat & Kontak',
                'address' => 'Gg. Buntu (Citanduy), Cipayung, Ciputat, Tangerang Selatan 15418',
                'whatsapp' => '0858 8385 8048',
                'hours' => 'Senin–Sabtu, 10.00–19.00, atau sesuai perjanjian',
                'maps_query' => 'GGP Elim, Gg. Buntu, Cipayung, Ciputat, Tangerang Selatan',
            ]],
        ];
    }

    /**
     * @return list<array{0: string, 1: string, 2: int, 3: string}>
     */
    private function schedules(): array
    {
        return [
            ['raya', 'Ibadah Raya I', 0, '08:00'],
            ['raya', 'Ibadah Raya II', 0, '17:00'],
            ['komisi', 'Sekolah Minggu', 0, '08:00'],
            ['komisi', 'Youth dan Pemuda/i', 0, '10:30'],
            ['komisi', 'Kaum Wanita / Kaum Pria', 4, '19:00'],
            ['komisi', 'Usia Emas', 3, '10:00'],
            ['doa', 'Menara Doa', 2, '19:00'],
            ['doa', 'Menara Doa', 6, '07:30'],
        ];
    }
}
