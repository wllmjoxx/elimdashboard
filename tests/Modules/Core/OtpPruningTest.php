<?php

namespace Tests\Modules\Core;

use App\Modules\Core\Models\LoginOtp;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtpPruningTest extends TestCase
{
    use RefreshDatabase;

    public function test_codes_older_than_a_day_are_pruned(): void
    {
        $old = $this->otp(now()->subDays(2));
        $fresh = $this->otp(now()->subHours(2));

        $this->artisan('model:prune', ['--model' => [LoginOtp::class]])->assertSuccessful();

        $this->assertModelMissing($old);
        $this->assertModelExists($fresh);
    }

    public function test_pruning_is_scheduled_daily(): void
    {
        $events = collect(app(Schedule::class)->events())
            ->filter(fn ($event) => str_contains((string) $event->command, 'model:prune') && str_contains((string) $event->command, 'LoginOtp'));

        $this->assertCount(1, $events);
        $this->assertSame('0 0 * * *', $events->first()->expression);
    }

    private function otp(\DateTimeInterface $createdAt): LoginOtp
    {
        $otp = new LoginOtp([
            'email' => 'jemaat@example.com',
            'code_hash' => 'hash',
            'expires_at' => $createdAt,
            'attempts' => 0,
        ]);
        $otp->created_at = $createdAt;
        $otp->save();

        return $otp;
    }
}
