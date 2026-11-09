<?php

namespace Tests\Modules\Members;

use App\Models\User;
use App\Modules\Core\Models\Church;
use App\Modules\Members\Enums\MemberStatus;
use App\Modules\Members\Models\Member;
use App\Modules\Members\Notifications\RegistrationReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private Church $church;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->church = Church::query()->create(['name' => 'GGP Elim', 'slug' => 'ggp-elim']);
    }

    public function test_registration_page_lists_active_branches(): void
    {
        Church::query()->create(['name' => 'Cabang Tutup', 'slug' => 'tutup', 'is_active' => false]);

        $this->get(route('register'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('members/auth/register')
                ->has('churches', 1)
                ->where('defaultChurchId', $this->church->id));
    }

    public function test_registering_creates_a_pending_account_without_signing_in(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Maria Siahaan',
            'email' => 'maria@example.com',
            'phone' => '0812 3456 7890',
            'church_id' => $this->church->id,
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertGuest();

        $member = Member::query()->where('email', 'maria@example.com')->firstOrFail();
        $this->assertSame(MemberStatus::Pending, $member->status);
        $this->assertSame($this->church->id, $member->church_id);
        $this->assertSame('0812 3456 7890', $member->phone);
        $this->assertNull($member->email_verified_at);
        $this->assertCount(0, $member->roles);

        Notification::assertSentTo($member, RegistrationReceived::class);
    }

    public function test_phone_is_optional_and_email_must_be_new(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);

        $this->post(route('register.store'), [
            'name' => 'Tanpa HP',
            'email' => 'baru@example.com',
            'church_id' => $this->church->id,
        ])->assertSessionHasNoErrors();

        $this->post(route('register.store'), [
            'name' => 'Duplikat',
            'email' => 'ada@example.com',
            'church_id' => $this->church->id,
        ])->assertSessionHasErrors('email');
    }

    public function test_inactive_or_unknown_branches_are_refused(): void
    {
        $closed = Church::query()->create(['name' => 'Tutup', 'slug' => 'tutup', 'is_active' => false]);

        $this->post(route('register.store'), ['name' => 'A', 'email' => 'a@example.com', 'church_id' => $closed->id])
            ->assertSessionHasErrors('church_id');
        $this->post(route('register.store'), ['name' => 'B', 'email' => 'b@example.com', 'church_id' => 999])
            ->assertSessionHasErrors('church_id');
    }

    public function test_registration_is_rate_limited(): void
    {
        foreach (range(1, 6) as $attempt) {
            $this->post(route('register.store'), ['name' => 'X', 'email' => "x{$attempt}@example.com", 'church_id' => $this->church->id]);
        }

        $this->post(route('register.store'), ['name' => 'X', 'email' => 'x7@example.com', 'church_id' => $this->church->id])
            ->assertTooManyRequests();
    }

    public function test_signed_in_users_cannot_open_the_registration_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('register'))
            ->assertRedirect();
    }
}
