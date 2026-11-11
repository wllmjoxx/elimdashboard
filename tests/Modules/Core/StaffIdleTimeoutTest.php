<?php

namespace Tests\Modules\Core;

use App\Models\User;
use App\Modules\Core\Actions\SyncRolesAndPermissions;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Http\Middleware\LogOutIdleStaff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class StaffIdleTimeoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(SyncRolesAndPermissions::class)->handle();
        config(['modules.core.staff_idle_minutes' => 30]);
    }

    public function test_active_staff_stay_signed_in(): void
    {
        $admin = $this->userWithRole(Role::Admin);

        $this->actingAs($admin)
            ->withSession([LogOutIdleStaff::SESSION_KEY => now()->subMinutes(10)->getTimestamp()])
            ->get('/dashboard')
            ->assertOk();

        $this->assertAuthenticatedAs($admin);
    }

    public function test_idle_staff_are_signed_out(): void
    {
        $this->actingAs($this->userWithRole(Role::PrayerTeam))
            ->withSession([LogOutIdleStaff::SESSION_KEY => now()->subMinutes(31)->getTimestamp()])
            ->get('/dashboard')
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertGuest();
    }

    public function test_members_keep_the_normal_session(): void
    {
        $member = $this->userWithRole(Role::Member);

        $this->actingAs($member)
            ->withSession([LogOutIdleStaff::SESSION_KEY => now()->subHours(3)->getTimestamp()])
            ->get('/dashboard')
            ->assertOk();

        $this->assertAuthenticatedAs($member);
    }

    public function test_remember_cookie_still_works_for_members(): void
    {
        $member = $this->userWithRole(Role::Member);

        $this->withCookie(...$this->rememberCookie($member))
            ->get('/dashboard')
            ->assertOk();

        $this->assertAuthenticatedAs($member);
    }

    public function test_remember_cookie_does_not_bring_staff_back(): void
    {
        $this->withCookie(...$this->rememberCookie($this->userWithRole(Role::Admin)))
            ->get('/dashboard')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function rememberCookie(User $user): array
    {
        $user->forceFill(['remember_token' => 'remember-token-value'])->save();

        return [
            Auth::guard('web')->getRecallerName(),
            $user->getAuthIdentifier().'|remember-token-value|'.$user->getAuthPassword(),
        ];
    }

    public function test_timeout_can_be_turned_off(): void
    {
        config(['modules.core.staff_idle_minutes' => 0]);
        $admin = $this->userWithRole(Role::Admin);

        $this->actingAs($admin)
            ->withSession([LogOutIdleStaff::SESSION_KEY => now()->subDay()->getTimestamp()])
            ->get('/dashboard')
            ->assertOk();
    }

    private function userWithRole(Role $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        return $user;
    }
}
