<?php

namespace App\Modules\Landing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Landing\Support\LandingContent;
use Inertia\Inertia;
use Inertia\Response;

/**
 * One page per navbar item (Tentang, Jadwal, Contact, plus Kebijakan Privasi
 * in the footer; Renungan Harian and
 * Form Doa are served by the Devotionals and Prayer modules),
 * each built from the matching landing sections.
 */
class PublicPageController extends Controller
{
    public function __construct(private readonly LandingContent $content) {}

    public function about(): Response
    {
        return $this->page('about', 'Tentang GGP Elim', 'Sejarah, misi, dan perjalanan GGP Jemaat Elim Ciputat.', ['about', 'mission']);
    }

    public function schedule(): Response
    {
        return $this->page('schedule', 'Jadwal Ibadah & Kegiatan', 'Ibadah raya, ibadah komisi, dan menara doa di GGP Elim Ciputat.', ['schedule']);
    }

    public function contact(): Response
    {
        return $this->page('contact', 'Contact', 'Alamat, WhatsApp, dan jam operasional sekretariat.', ['contact']);
    }

    public function privacy(): Response
    {
        return $this->page('privacy', 'Kebijakan Privasi', 'Cara GGP Elim mengumpulkan, memakai, dan melindungi data pribadi Anda.', ['privacy']);
    }

    /**
     * @param  list<string>  $keys
     */
    private function page(string $page, string $title, string $description, array $keys): Response
    {
        $sections = $this->content->sections($keys);

        abort_if($sections === [], 404);

        return Inertia::render('landing/public/page', [
            'page' => $page,
            'title' => $title,
            'description' => $description,
            'sections' => $sections,
            'scheduleGroups' => in_array('schedule', $keys, true) ? $this->content->scheduleGroups() : [],
        ])->withViewData('seo', [
            'title' => $title,
            'description' => $description,
            'schema' => $page === 'contact',
        ]);
    }
}
