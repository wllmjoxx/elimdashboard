<?php

namespace Tests\Modules\Core;

use App\Models\User;
use App\Modules\Core\Models\AuditLog;
use App\Modules\Core\Models\Church;
use App\Modules\Core\Support\Captcha;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CaptchaAndAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_captcha_is_off_without_keys(): void
    {
        Http::fake();

        $captcha = app(Captcha::class);

        $this->assertFalse($captcha->enabled());
        $this->assertNull($captcha->siteKey());
        $this->assertTrue($captcha->verify(null));
        Http::assertNothingSent();
    }

    public function test_captcha_fails_closed_on_missing_token_or_network_error(): void
    {
        config(['modules.core.captcha.site_key' => 'site', 'modules.core.captcha.secret_key' => 'secret']);
        Http::fake(fn () => throw new ConnectionException('down'));

        $captcha = app(Captcha::class);

        $this->assertSame('site', $captcha->siteKey());
        $this->assertFalse($captcha->verify(''));
        $this->assertFalse($captcha->verify('token'));
    }

    public function test_audit_log_records_user_subject_metadata_and_branch(): void
    {
        $church = Church::query()->create(['name' => 'GGP Elim', 'slug' => 'ggp-elim']);
        $user = User::factory()->create(['church_id' => $church->id]);
        $this->actingAs($user);

        $log = AuditLog::record('test.action', $church, ['note' => 'ok']);

        $this->assertSame('test.action', $log->action);
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame($church->id, $log->subject_id);
        $this->assertSame(['note' => 'ok'], $log->fresh()->metadata);
        $this->assertSame($church->id, $log->church_id);
    }
}
