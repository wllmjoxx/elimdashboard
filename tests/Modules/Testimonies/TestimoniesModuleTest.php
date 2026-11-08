<?php

namespace Tests\Modules\Testimonies;

use App\Models\User;
use App\Support\Modules\ModuleRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TestimoniesModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_module_is_registered(): void
    {
        $this->assertNotNull(app(ModuleRegistry::class)->find('testimonies'));
    }

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('testimonies.index'))->assertRedirect(route('login'));
    }

    public function test_verified_users_can_open_the_index_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('testimonies.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('testimonies/index'));
    }
}
