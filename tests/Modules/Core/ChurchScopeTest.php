<?php

namespace Tests\Modules\Core;

use App\Models\User;
use App\Modules\Core\Actions\SyncRolesAndPermissions;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\Church;
use App\Modules\Core\Models\Concerns\BelongsToChurch;
use App\Modules\Core\Support\CurrentChurch;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ChurchScopeTest extends TestCase
{
    use RefreshDatabase;

    private Church $elim;

    private Church $other;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('scoped_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->nullable();
            $table->string('title');
        });

        app(SyncRolesAndPermissions::class)->handle();

        $this->elim = Church::query()->create(['name' => 'GGP Elim', 'slug' => 'ggp-elim']);
        $this->other = Church::query()->create(['name' => 'GGP Lain', 'slug' => 'ggp-lain']);

        ScopedNote::query()->create(['church_id' => $this->elim->id, 'title' => 'elim']);
        ScopedNote::query()->create(['church_id' => $this->other->id, 'title' => 'lain']);
    }

    public function test_code_outside_a_web_request_is_not_scoped(): void
    {
        $this->actingAs($this->userWithRole(Role::Admin, $this->other));

        $this->assertSame(['elim', 'lain'], $this->titles());
    }

    public function test_staff_see_only_their_branch(): void
    {
        $this->actingAs($this->userWithRole(Role::Admin, $this->other));

        $this->assertSame(['lain'], $this->restricted(fn () => $this->titles()));
    }

    public function test_visitors_see_the_default_branch(): void
    {
        $this->assertSame(['elim'], $this->restricted(fn () => $this->titles()));
    }

    public function test_users_without_a_branch_see_the_default_branch(): void
    {
        $this->actingAs($this->userWithRole(Role::Member, null));

        $this->assertSame(['elim'], $this->restricted(fn () => $this->titles()));
    }

    public function test_superadmin_sees_every_branch(): void
    {
        $this->actingAs($this->userWithRole(Role::Superadmin, $this->elim));

        $this->assertSame(['elim', 'lain'], $this->restricted(fn () => $this->titles()));
    }

    public function test_nothing_is_visible_when_no_default_branch_exists(): void
    {
        config(['modules.core.default_church' => 'missing']);

        $this->assertSame([], $this->restricted(fn () => $this->titles()));
    }

    public function test_new_records_get_the_current_branch(): void
    {
        $this->actingAs($this->userWithRole(Role::Admin, $this->other));

        $note = $this->restricted(fn () => ScopedNote::query()->create(['title' => 'baru']));

        $this->assertSame($this->other->id, $note->church_id);
    }

    public function test_new_records_outside_a_request_get_the_default_branch(): void
    {
        $note = ScopedNote::query()->create(['title' => 'seed']);

        $this->assertSame($this->elim->id, $note->church_id);
    }

    public function test_web_requests_are_scoped_and_released_afterwards(): void
    {
        Route::middleware('web')->get('/_test/scoped-notes', fn () => $this->titles());

        $this->actingAs($this->userWithRole(Role::Admin, $this->other))
            ->get('/_test/scoped-notes')
            ->assertExactJson(['lain']);

        $this->assertFalse(app(CurrentChurch::class)->restricts());
        $this->assertSame(['elim', 'lain'], $this->titles());
    }

    public function test_without_scope_sees_every_branch_inside_a_request(): void
    {
        $this->actingAs($this->userWithRole(Role::Admin, $this->other));

        $titles = $this->restricted(fn () => app(CurrentChurch::class)->withoutScope(fn () => $this->titles()));

        $this->assertSame(['elim', 'lain'], $titles);
    }

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    private function restricted(Closure $callback): mixed
    {
        $current = app(CurrentChurch::class);
        $current->restrict();

        try {
            return $callback();
        } finally {
            $current->release();
        }
    }

    /**
     * @return list<string>
     */
    private function titles(): array
    {
        return ScopedNote::query()->orderBy('title')->pluck('title')->all();
    }

    private function userWithRole(Role $role, ?Church $church): User
    {
        $user = User::factory()->create(['church_id' => $church?->id]);
        $user->assignRole($role->value);

        return $user;
    }
}

class ScopedNote extends Model
{
    use BelongsToChurch;

    public $timestamps = false;

    protected $guarded = [];
}
