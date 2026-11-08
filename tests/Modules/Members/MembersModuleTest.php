<?php

namespace Tests\Modules\Members;

use App\Models\User;
use App\Support\Modules\ModuleRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MembersModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_module_is_registered(): void
    {
        $this->assertNotNull(app(ModuleRegistry::class)->find('members'));
    }

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('members.index'))->assertRedirect(route('login'));
    }

    public function test_verified_users_can_open_the_index_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('members.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('members/index'));
    }
}
