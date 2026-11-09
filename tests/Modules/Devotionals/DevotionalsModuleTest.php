<?php

namespace Tests\Modules\Devotionals;

use App\Models\User;
use App\Modules\Core\Actions\SyncRolesAndPermissions;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\Church;
use App\Modules\Devotionals\Database\Seeders\DevotionalsSeeder;
use App\Modules\Devotionals\Enums\DevotionalStatus;
use App\Modules\Devotionals\Models\Devotional;
use App\Support\Modules\ModuleRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DevotionalsModuleTest extends TestCase
{
    use RefreshDatabase;

    private Church $elim;

    private Church $other;

    protected function setUp(): void
    {
        parent::setUp();

        app(SyncRolesAndPermissions::class)->handle();
        $this->elim = Church::query()->create(['name' => 'GGP Elim', 'slug' => 'ggp-elim']);
        $this->other = Church::query()->create(['name' => 'GGP Lain', 'slug' => 'ggp-lain']);
    }

    public function test_module_is_registered(): void
    {
        $this->assertNotNull(app(ModuleRegistry::class)->find('devotionals'));
    }

    public function test_guests_are_redirected_and_members_are_forbidden(): void
    {
        $this->get(route('devotionals.index'))->assertRedirect(route('login'));
        $this->actingAs($this->user(Role::Member))->get(route('devotionals.index'))->assertForbidden();
    }

    public function test_servant_writes_a_draft_that_stays_unpublished(): void
    {
        $servant = $this->user(Role::Servant);

        $this->actingAs($servant)
            ->post(route('devotionals.store'), $this->payload(['publish' => true]))
            ->assertRedirect(route('devotionals.index', ['tab' => 'draft']));

        $devotional = Devotional::query()->withoutGlobalScopes()->firstOrFail();
        $this->assertSame(DevotionalStatus::Draft, $devotional->status);
        $this->assertSame($servant->id, $devotional->created_by);
        $this->assertSame($this->elim->id, $devotional->church_id);
        $this->assertSame('gembala-yang-baik', $devotional->slug);

        $this->actingAs($servant)->post(route('devotionals.publish', $devotional))->assertForbidden();
    }

    public function test_servant_edits_only_own_drafts(): void
    {
        $servant = $this->user(Role::Servant);
        $own = $this->devotional(['created_by' => $servant->id]);
        $others = $this->devotional(['created_by' => $this->user(Role::Servant)->id]);
        $published = $this->devotional(['created_by' => $servant->id, 'status' => DevotionalStatus::Published]);

        $this->actingAs($servant)->put(route('devotionals.update', $own), $this->payload(['title' => 'Baru']))->assertRedirect();
        $this->assertSame('Baru', $own->refresh()->title);

        $this->actingAs($servant)->put(route('devotionals.update', $others), $this->payload())->assertForbidden();
        $this->actingAs($servant)->put(route('devotionals.update', $published), $this->payload())->assertForbidden();
        $this->actingAs($servant)->delete(route('devotionals.destroy', $own))->assertRedirect();
        $this->assertModelMissing($own);
    }

    public function test_admin_publishes_today_or_schedules_and_can_unpublish(): void
    {
        $admin = $this->user(Role::Admin);
        $draft = $this->devotional();
        $future = $this->devotional(['publish_date' => Devotional::today()->addDays(3)]);

        $this->actingAs($admin)->post(route('devotionals.publish', $draft))->assertRedirect();
        $this->actingAs($admin)->post(route('devotionals.publish', $future))->assertRedirect();

        $draft->refresh();
        $this->assertSame(DevotionalStatus::Published, $draft->status);
        $this->assertSame($admin->id, $draft->published_by);
        $this->assertFalse($draft->isScheduled());
        $this->assertTrue($future->refresh()->isScheduled());

        $this->actingAs($admin)
            ->get(route('devotionals.index', ['tab' => 'scheduled']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('devotionals.data', 1)
                ->where('devotionals.data.0.stateLabel', 'Terjadwal')
                ->where('tabs.1.count', 1)
                ->where('tabs.2.count', 1));

        $this->actingAs($admin)->post(route('devotionals.unpublish', $draft))->assertRedirect();
        $this->assertSame(DevotionalStatus::Draft, $draft->refresh()->status);
    }

    public function test_admin_can_create_and_publish_in_one_step(): void
    {
        $this->actingAs($this->user(Role::Admin))
            ->post(route('devotionals.store'), $this->payload(['publish' => true]))
            ->assertRedirect(route('devotionals.index', ['tab' => 'published']));

        $this->assertSame(DevotionalStatus::Published, Devotional::query()->withoutGlobalScopes()->firstOrFail()->status);
    }

    public function test_devotionals_of_another_branch_are_not_found(): void
    {
        $outside = $this->devotional(['church_id' => $this->other->id]);

        $this->actingAs($this->user(Role::Admin))
            ->get(route('devotionals.edit', $outside))
            ->assertNotFound();
    }

    public function test_slugs_stay_unique_within_a_branch(): void
    {
        $first = $this->devotional(['title' => 'Kasih']);
        $second = $this->devotional(['title' => 'Kasih']);
        $elsewhere = $this->devotional(['title' => 'Kasih', 'church_id' => $this->other->id]);

        $this->assertSame('kasih', $first->slug);
        $this->assertSame('kasih-2', $second->slug);
        $this->assertSame('kasih', $elsewhere->slug);
    }

    public function test_validation_requires_title_reference_body_and_date(): void
    {
        $this->actingAs($this->user(Role::Admin))
            ->post(route('devotionals.store'), [])
            ->assertSessionHasErrors(['title', 'scripture_ref', 'body', 'publish_date']);
    }

    public function test_seeder_adds_a_week_once(): void
    {
        $this->seed(DevotionalsSeeder::class);
        $this->seed(DevotionalsSeeder::class);

        $all = Devotional::query()->withoutGlobalScopes()->get();
        $this->assertCount(7, $all);
        $this->assertCount(1, $all->where('status', DevotionalStatus::Draft));
        $this->assertCount(1, $all->filter->isScheduled());
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'title' => 'Gembala yang Baik',
            'scripture_ref' => 'Mazmur 23:1',
            'scripture_text' => 'TUHAN adalah gembalaku, takkan kekurangan aku.',
            'body' => "Paragraf satu.\n\nParagraf dua.",
            'publish_date' => Devotional::today()->toDateString(),
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function devotional(array $attributes = []): Devotional
    {
        $devotional = new Devotional([
            'title' => $attributes['title'] ?? 'Renungan',
            'scripture_ref' => 'Yohanes 3:16',
            'body' => 'Isi.',
            'publish_date' => $attributes['publish_date'] ?? Devotional::today(),
        ]);
        $devotional->church_id = $attributes['church_id'] ?? $this->elim->id;
        $devotional->created_by = $attributes['created_by'] ?? null;
        $devotional->status = $attributes['status'] ?? DevotionalStatus::Draft;
        $devotional->save();

        return $devotional;
    }

    private function user(Role $role): User
    {
        $user = User::factory()->create(['church_id' => $this->elim->id]);
        $user->assignRole($role->value);

        return $user;
    }
}
