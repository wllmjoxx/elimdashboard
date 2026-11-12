<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Announcements\Models\Announcement;
use App\Modules\Devotionals\Models\Devotional;
use App\Modules\Events\Models\Event;
use App\Modules\Gallery\Models\Album;
use App\Modules\Landing\Support\SectionEditor;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Opens every page as a visitor and as each role with the seeded data, and
 * fails on any server error (5xx). Access rules themselves are tested per
 * module; here 302/403/404 are fine, 500 never is.
 */
class PageSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_page_returns_a_server_error_for_any_role(): void
    {
        $this->seed(DatabaseSeeder::class);

        $accounts = [
            'tamu' => null,
            'jemaat' => 'jemaat@example.com',
            'pelayan' => 'pelayan@example.com',
            'tim doa' => 'doa@example.com',
            'admin' => 'admin@example.com',
            'superadmin' => 'test@example.com',
        ];

        $failures = [];

        foreach ($accounts as $label => $email) {
            $this->app['auth']->guard('web')->logout();
            $this->flushSession();

            if ($email !== null) {
                $this->actingAs(User::query()->where('email', $email)->firstOrFail());
            }

            foreach ($this->urls() as $url) {
                $status = $this->get($url)->getStatusCode();

                if ($status >= 500) {
                    $failures[] = "{$label}: GET {$url} → {$status}";
                }
            }
        }

        $this->assertSame([], $failures, "Pages with server errors:\n".implode("\n", $failures));
    }

    /**
     * @return list<string>
     */
    private function urls(): array
    {
        $devotional = Devotional::query()->withoutGlobalScopes()->first();
        $album = Album::query()->withoutGlobalScopes()->first();
        $member = User::query()->where('email', 'jemaat@example.com')->first();

        $urls = [
            '/', '/tentang', '/jadwal', '/kontak', '/kebijakan-privasi', '/doa', '/kesaksian',
            '/renungan', '/galeri', '/sitemap.xml', '/robots.txt',
            '/login', '/login/kode', '/login/kode/verifikasi?email=jemaat@example.com', '/register',
            '/forgot-password', '/reset-password/token-contoh?email=jemaat@example.com',
            '/email/verify', '/two-factor-challenge', '/user/confirm-password',
            '/dashboard', '/profil', '/doa/riwayat',
            '/settings', '/settings/profile', '/settings/security', '/settings/appearance',
            '/admin/core', '/admin/members', '/admin/prayer', '/admin/prayer/print',
            '/admin/testimonies', '/admin/devotionals', '/admin/devotionals/create', '/admin/gallery',
            '/admin/landing', '/admin/landing/jadwal', '/admin/landing/segmen', '/admin/landing/video',
            '/kegiatan', '/pengumuman', '/admin/events', '/admin/events/create',
            '/admin/announcements', '/admin/announcements/create',
        ];

        $event = Event::query()->withoutGlobalScopes()->first();
        $announcement = Announcement::query()->withoutGlobalScopes()->first();

        if ($event) {
            $urls[] = "/kegiatan/{$event->slug}";
            $urls[] = "/admin/events/{$event->id}/edit";
        }

        if ($announcement) {
            $urls[] = "/pengumuman/{$announcement->slug}";
            $urls[] = "/admin/announcements/{$announcement->id}/edit";
        }

        foreach (SectionEditor::EDITABLE as $key) {
            $urls[] = "/admin/landing/sections/{$key}";
        }

        if ($devotional) {
            $urls[] = "/renungan/{$devotional->slug}";
            $urls[] = "/admin/devotionals/{$devotional->id}/edit";
        }

        if ($album) {
            $urls[] = "/galeri/{$album->slug}";
            $urls[] = "/admin/gallery/{$album->id}";
        }

        if ($member) {
            $urls[] = "/admin/members/{$member->id}";
            $urls[] = "/member-photos/{$member->id}";
            $urls[] = parse_url(URL::temporarySignedRoute('members.password.create', now()->addHour(), ['member' => $member->id]), PHP_URL_PATH)
                .'?'.parse_url(URL::temporarySignedRoute('members.password.create', now()->addHour(), ['member' => $member->id]), PHP_URL_QUERY);
        }

        return $urls;
    }
}
