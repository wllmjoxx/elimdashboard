<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Members\Enums\MemberStatus;
use App\Modules\Members\Http\Controllers\SetPasswordController;
use App\Modules\Members\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * The online preview runs behind cloudflared on this machine: https and the
 * visitor's IP arrive in X-Forwarded-* headers from 127.0.0.1.
 */
class TunnelProxyTest extends TestCase
{
    use RefreshDatabase;

    private const HOST = 'contoh-pratinjau.trycloudflare.com';

    protected function setUp(): void
    {
        parent::setUp();

        // Links are generated outside a request (queued email), so the
        // scheme comes from APP_URL, not from whatever .env holds here.
        config(['app.url' => 'https://'.self::HOST]);
        URL::forceRootUrl('https://'.self::HOST);
        URL::forceScheme('https');
    }

    public function test_email_links_signed_for_https_open_through_the_tunnel(): void
    {
        $member = Member::query()->findOrFail(User::factory()->create()->id);
        $member->status = MemberStatus::Active;
        $member->save();

        $link = SetPasswordController::link($member);
        $path = parse_url($link, PHP_URL_PATH).'?'.parse_url($link, PHP_URL_QUERY);

        // cloudflared reaches PHP over plain http; the signature covers https.
        $this->withServerVariables($this->viaTunnel('203.0.113.7'))
            ->get('http://'.self::HOST.$path)
            ->assertOk();

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.20'])
            ->get('http://'.self::HOST.$path)
            ->assertForbidden();
    }

    public function test_requests_see_the_visitors_ip_and_https(): void
    {
        $this->app['router']->get('/_proxy-check', fn () => [
            'ip' => request()->ip(),
            'secure' => request()->isSecure(),
        ]);

        $this->withServerVariables($this->viaTunnel('203.0.113.7'))
            ->getJson('http://'.self::HOST.'/_proxy-check')
            ->assertExactJson(['ip' => '203.0.113.7', 'secure' => true]);
    }

    public function test_forwarded_headers_from_other_machines_are_ignored(): void
    {
        $this->app['router']->get('/_proxy-check', fn () => ['ip' => request()->ip()]);

        $this->withServerVariables([...$this->viaTunnel('203.0.113.7'), 'REMOTE_ADDR' => '198.51.100.20'])
            ->getJson('http://'.self::HOST.'/_proxy-check')
            ->assertExactJson(['ip' => '198.51.100.20']);
    }

    /**
     * @return array<string, string>
     */
    private function viaTunnel(string $visitorIp): array
    {
        return [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_X_FORWARDED_FOR' => $visitorIp,
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_PORT' => '443',
        ];
    }
}
