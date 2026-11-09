<?php

namespace Tests\Modules\Members;

use App\Models\User;
use App\Modules\Members\Enums\MemberStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ActiveAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_accounts_can_sign_in(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    /**
     * @return array<string, array{0: MemberStatus}>
     */
    public static function blockedStatuses(): array
    {
        return [
            'pending' => [MemberStatus::Pending],
            'rejected' => [MemberStatus::Rejected],
            'inactive' => [MemberStatus::Inactive],
        ];
    }

    #[DataProvider('blockedStatuses')]
    public function test_other_accounts_are_told_why_they_cannot_sign_in(MemberStatus $status): void
    {
        $user = User::factory()->create();
        $user->forceFill(['status' => $status->value])->save();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => $status->loginMessage()]);

        $this->assertGuest();
    }

    public function test_wrong_password_gives_the_normal_error(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['status' => MemberStatus::Inactive->value])->save();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'salah'])
            ->assertSessionHasErrors('email');

        $this->assertNotSame(MemberStatus::Inactive->loginMessage(), session('errors')->first('email'));
    }

    public function test_deactivated_accounts_are_signed_out_on_their_next_request(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $user->forceFill(['status' => MemberStatus::Inactive->value])->save();

        $this->actingAs($user->fresh())
            ->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
