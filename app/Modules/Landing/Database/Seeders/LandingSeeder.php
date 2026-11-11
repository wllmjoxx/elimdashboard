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
            // The pastor section waits hidden until Admin fills in the real
            // name, bio and photo.
            $values = ['content' => $content, 'is_active' => $key !== 'pastor', 'sort_order' => ($order + 1) * 10];

            // Every section is edited by Admin now: only add the missing ones,
            // never overwrite what Admin changed.
            SiteSection::query()->firstOrCreate($attributes, $values);
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
                'headline' => 'Selamat Datang',
                'subheadline' => 'di GGP ELIM Rumah Kasih Karunia',
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
            ['pastor', [
                'label' => 'Gembala Sidang',
                'name' => 'Nama Gembala Sidang',
                'paragraphs' => [
                    'Tulis bio singkat gembala sidang di sini: asal, panggilan pelayanan, dan sejak kapan menggembalakan GGP Jemaat Elim Ciputat.',
                ],
                'more_label' => 'Lihat selengkapnya',
                'more_href' => '/tentang',
                'social_name' => null,
                'instagram' => null,
                'youtube' => null,
                'facebook' => null,
                'photo' => null,
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
            // Starting text written against the actual features (UU No. 27
            // Tahun 2022 tentang Pelindungan Data Pribadi); the church should
            // review it before go-live and can edit it from the admin panel.
            ['privacy', [
                'title' => 'Kebijakan Privasi',
                'effective' => '11 November 2026',
                'intro' => 'GGP Jemaat Elim Ciputat menghargai kepercayaan Anda. Halaman ini menjelaskan data apa yang kami kumpulkan lewat situs ini, untuk apa, siapa yang dapat melihatnya, dan hak Anda atas data tersebut, sesuai Undang-Undang No. 27 Tahun 2022 tentang Pelindungan Data Pribadi.',
                'points' => [
                    [
                        'heading' => 'Data yang kami kumpulkan',
                        'body' => "Akun jemaat: nama, email, nomor telepon, dan cabang gereja saat mendaftar, serta data profil yang Anda isi sendiri (nama panggilan, foto, tanggal lahir, jenis kelamin, alamat, status keanggotaan, dan baptisan).\nForm doa: nama lengkap, usia, nomor telepon, alamat, dan isi pokok doa.\nKesaksian: judul, isi, kategori, dan nama tampilan yang Anda pilih. Kami tidak menyimpan identitas akun pengirim kesaksian.\nCatatan teknis: alamat IP dan waktu akses untuk keamanan, pencegahan spam, dan log aktivitas pengelola.",
                    ],
                    [
                        'heading' => 'Untuk apa data dipakai',
                        'body' => "Mengelola akun dan keanggotaan jemaat, termasuk persetujuan pendaftaran oleh Admin.\nMendoakan pokok doa yang Anda kirim dan, bila perlu, menghubungi Anda untuk pelayanan doa.\nMenampilkan kesaksian yang sudah disetujui.\nMengirim email yang berkaitan dengan akun, misalnya kode masuk dan kabar persetujuan.\nKami tidak menjual data Anda dan tidak memakainya untuk iklan.",
                    ],
                    [
                        'heading' => 'Siapa yang dapat melihat data',
                        'body' => "Isi pokok doa hanya dapat dibaca oleh Tim Doa, Admin, dan Superadmin. Alamat dan isi doa disimpan dalam bentuk terenkripsi.\nData profil jemaat hanya dapat dilihat oleh Admin cabang Anda dan Superadmin. Anda dapat mengatur data profil mana yang boleh dilihat sesama jemaat.\nKesaksian tampil untuk umum hanya setelah disetujui moderator, dengan nama tampilan yang Anda pilih.",
                    ],
                    [
                        'heading' => 'Pihak ketiga',
                        'body' => 'Kami memakai layanan pihak ketiga hanya sejauh diperlukan agar situs berjalan: penyedia server dan jaringan (termasuk Cloudflare untuk keamanan dan captcha anti-spam) serta penyedia layanan email untuk mengirim email akun. Mereka memproses data atas nama kami dan tidak berhak memakainya untuk keperluan lain.',
                    ],
                    [
                        'heading' => 'Cookie',
                        'body' => 'Situs ini hanya memakai cookie yang diperlukan: menjaga Anda tetap masuk, melindungi form dari penyalahgunaan, dan mengingat pilihan tampilan (terang atau gelap). Kami tidak memakai cookie iklan atau pelacak.',
                    ],
                    [
                        'heading' => 'Penyimpanan dan keamanan',
                        'body' => "Data disimpan di server dengan koneksi terenkripsi (HTTPS) dan dicadangkan setiap hari. Password disimpan dalam bentuk hash, dan kode masuk lewat email hanya berlaku sekali dalam waktu singkat. Setiap tindakan pengelola atas data sensitif dicatat dalam log audit.\nTim Doa mengarsipkan pokok doa setelah didoakan. Data akun disimpan selama akun aktif dan dihapus setelah permintaan penghapusan disetujui, kecuali yang wajib disimpan menurut hukum.",
                    ],
                    [
                        'heading' => 'Hak Anda',
                        'body' => "Anda berhak melihat, memperbarui, dan memperbaiki data profil Anda kapan saja lewat halaman Profil.\nAnda berhak meminta akun dan data pribadi Anda dihapus lewat tombol \"Minta hapus akun\" di halaman Profil. Admin akan memproses permintaan tersebut.\nAnda berhak menarik persetujuan dan mengajukan pertanyaan atau keberatan atas pemrosesan data Anda melalui kontak di bawah.",
                    ],
                    [
                        'heading' => 'Anak-anak',
                        'body' => 'Akun jemaat ditujukan untuk remaja dan dewasa. Bila Anda mengirim data tentang anak, misalnya dalam pokok doa, pastikan orang tua atau wali menyetujuinya.',
                    ],
                    [
                        'heading' => 'Perubahan kebijakan',
                        'body' => 'Kebijakan ini dapat diperbarui sewaktu-waktu. Tanggal berlaku di atas menunjukkan versi terbaru.',
                    ],
                    [
                        'heading' => 'Hubungi kami',
                        'body' => 'Pertanyaan tentang data pribadi dapat disampaikan ke sekretariat GGP Elim Ciputat melalui WhatsApp atau alamat yang tercantum di halaman Contact.',
                    ],
                ],
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
