<?php

namespace Tests\Modules\Core;

use App\Models\User;
use App\Modules\Core\Models\LoginOtp;
use App\Modules\Core\Notifications\LoginCode;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OtpLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function test_request_page_renders(): void
    {
        $this->get(route('core.otp.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('core/auth/otp-request')->where('minutes', 10));
    }

    public function test_a_code_is_sent_and_signs_the_user_in_once(): void
    {
        $user = User::factory()->create(['email' => 'maria@example.com']);

        $this->post(route('core.otp.store'), ['email' => 'Maria@Example.com'])
            ->assertRedirect(route('core.otp.verify', ['email' => 'Maria@Example.com']))
            ->assertSessionHas('status');

        $code = $this->sentCode($user);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->assertNotSame($code, LoginOtp::query()->firstOrFail()->code_hash);

        $this->post(route('core.otp.update'), ['email' => 'maria@example.com', 'code' => $code])
            ->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);

        auth()->logout();

        $this->post(route('core.otp.update'), ['email' => 'maria@example.com', 'code' => $code])
            ->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_code_email_skips_the_queue(): void
    {
        $this->assertNotInstanceOf(ShouldQueue::class, new LoginCode('123456', 10));

        Queue::fake();
        $user = User::factory()->create(['email' => 'cepat@example.com']);

        $this->post(route('core.otp.store'), ['email' => 'cepat@example.com']);

        Queue::assertNothingPushed();
        $this->assertMatchesRegularExpression('/^\d{6}$/', $this->sentCode($user));
    }

    public function test_unknown_or_inactive_emails_get_the_same_answer_and_no_code(): void
    {
        $inactive = User::factory()->create(['email' => 'nonaktif@example.com']);
        $inactive->forceFill(['status' => 'inactive'])->save();

        foreach (['tidakada@example.com', 'nonaktif@example.com'] as $email) {
            $this->post(route('core.otp.store'), ['email' => $email])
                ->assertRedirect(route('core.otp.verify', ['email' => $email]))
                ->assertSessionHas('status');
        }

        Notification::assertNothingSent();
        $this->assertSame(0, LoginOtp::query()->count());
    }

    public function test_wrong_codes_count_and_lock_the_code(): void
    {
        $user = User::factory()->create(['email' => 'maria@example.com']);
        $this->post(route('core.otp.store'), ['email' => 'maria@example.com']);
        $code = $this->sentCode($user);
        $wrong = $code === '000000' ? '111111' : '000000';

        foreach (range(1, 5) as $attempt) {
            $this->post(route('core.otp.update'), ['email' => 'maria@example.com', 'code' => $wrong])->assertSessionHasErrors('code');
        }

        $this->assertSame(5, LoginOtp::query()->firstOrFail()->attempts);

        // Even the right code no longer works after five wrong ones.
        $this->post(route('core.otp.update'), ['email' => 'maria@example.com', 'code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_codes_expire(): void
    {
        $user = User::factory()->create(['email' => 'maria@example.com']);
        $this->post(route('core.otp.store'), ['email' => 'maria@example.com']);
        $code = $this->sentCode($user);

        Carbon::setTestNow(now()->addMinutes(11));

        $this->post(route('core.otp.update'), ['email' => 'maria@example.com', 'code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();

        Carbon::setTestNow();
    }

    public function test_a_new_code_replaces_the_old_one(): void
    {
        $user = User::factory()->create(['email' => 'maria@example.com']);

        $this->post(route('core.otp.store'), ['email' => 'maria@example.com']);
        $first = $this->sentCode($user);
        $this->post(route('core.otp.store'), ['email' => 'maria@example.com']);

        $this->assertSame(1, LoginOtp::query()->count());

        $codes = [];
        Notification::assertSentTo($user, LoginCode::class, function (LoginCode $notification) use (&$codes) {
            $codes[] = $notification->code;

            return true;
        });
        $latest = end($codes);

        if ($first !== $latest) {
            $this->post(route('core.otp.update'), ['email' => 'maria@example.com', 'code' => $first])->assertSessionHasErrors('code');
        }

        $this->post(route('core.otp.update'), ['email' => 'maria@example.com', 'code' => $latest])->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_signing_in_with_a_code_verifies_the_email(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'baru@example.com']);
        $this->post(route('core.otp.store'), ['email' => 'baru@example.com']);

        $this->post(route('core.otp.update'), ['email' => 'baru@example.com', 'code' => $this->sentCode($user)]);

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_two_factor_accounts_still_face_the_challenge(): void
    {
        $user = User::factory()->withTwoFactor()->create(['email' => 'aman@example.com']);
        $this->post(route('core.otp.store'), ['email' => 'aman@example.com']);

        $this->post(route('core.otp.update'), ['email' => 'aman@example.com', 'code' => $this->sentCode($user)])
            ->assertRedirect(route('two-factor.login'))
            ->assertSessionHas('login.id', $user->id);

        $this->assertGuest();
    }

    public function test_sending_is_rate_limited_per_email(): void
    {
        User::factory()->create(['email' => 'maria@example.com']);

        foreach (range(1, 3) as $attempt) {
            $this->post(route('core.otp.store'), ['email' => 'maria@example.com'])->assertRedirect();
        }

        $this->post(route('core.otp.store'), ['email' => 'maria@example.com'])->assertTooManyRequests();
    }

    public function test_verify_page_needs_an_email_and_signed_in_users_are_sent_away(): void
    {
        $this->get(route('core.otp.verify'))->assertRedirect(route('core.otp.create'));

        $this->get(route('core.otp.verify', ['email' => 'maria@example.com']))
            ->assertInertia(fn (Assert $page) => $page->component('core/auth/otp-verify')->where('email', 'maria@example.com'));

        $this->actingAs(User::factory()->create())->get(route('core.otp.create'))->assertRedirect();
    }

    private function sentCode(User $user): string
    {
        $code = null;

        Notification::assertSentTo($user, LoginCode::class, function (LoginCode $notification) use (&$code) {
            $code = $notification->code;

            return true;
        });

        return (string) $code;
    }
}
