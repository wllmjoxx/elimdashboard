<?php

namespace Tests\Modules\Testimonies;

use App\Models\User;
use App\Modules\Core\Actions\SyncRolesAndPermissions;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\Church;
use App\Modules\Landing\Database\Seeders\LandingSeeder;
use App\Modules\Testimonies\Database\Seeders\TestimoniesSeeder;
use App\Modules\Testimonies\Enums\TestimonyStatus;
use App\Modules\Testimonies\Models\Testimony;
use App\Support\Modules\ModuleRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TestimoniesModuleTest extends TestCase
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
        $this->assertNotNull(app(ModuleRegistry::class)->find('testimonies'));
    }

    public function test_table_has_no_column_that_could_identify_the_sender(): void
    {
        foreach (['user_id', 'ip', 'ip_address', 'email', 'session_id', 'user_agent'] as $column) {
            $this->assertFalse(Schema::hasColumn('testimonies', $column), $column);
        }
    }

    public function test_guests_and_members_submit_without_any_identity_being_stored(): void
    {
        $this->post('/kesaksian', $this->payload())
            ->assertRedirect(route('testimonies.public.index'))
            ->assertSessionHas('testimony_sent');

        $member = $this->user(Role::Member);
        $this->actingAs($member)->post('/kesaksian', $this->payload(['display_name' => '']));

        $rows = Testimony::query()->withoutGlobalScopes()->get();
        $this->assertCount(2, $rows);
        $this->assertTrue($rows->every(fn (Testimony $testimony) => $testimony->status === TestimonyStatus::Pending));
        $this->assertSame('Anonim', $rows[1]->shownName());
        $this->assertSame($this->elim->id, $rows[0]->church_id);

        $attributes = array_keys($rows[1]->getAttributes());
        $this->assertSame([], array_intersect($attributes, ['user_id', 'ip', 'email']));
    }

    public function test_pending_testimonies_are_not_public(): void
    {
        $this->testimony('Masih menunggu');
        $this->testimony('Sudah disetujui', TestimonyStatus::Approved);

        $this->get('/kesaksian')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('testimonies/public/index')
                ->has('testimonies.data', 1)
                ->where('testimonies.data.0.title', 'Sudah disetujui')
                ->where('testimonies.data.0.name', 'Anonim'));
    }

    public function test_form_validation_honeypot_and_captcha(): void
    {
        $this->post('/kesaksian', ['website' => ''])->assertSessionHasErrors(['title', 'body', 'category']);
        $this->post('/kesaksian', $this->payload(['website' => 'x']))->assertSessionHasErrors('website');

        config(['modules.core.captcha.site_key' => 'site', 'modules.core.captcha.secret_key' => 'secret']);
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => false])]);

        // Members are checked too: the sender is never known.
        $this->actingAs($this->user(Role::Member))
            ->post('/kesaksian', $this->payload(['captcha_token' => 'bad']))
            ->assertSessionHasErrors('captcha_token');

        $this->assertSame(0, Testimony::query()->withoutGlobalScopes()->count());
    }

    public function test_submissions_are_rate_limited(): void
    {
        foreach (range(1, 3) as $attempt) {
            $this->post('/kesaksian', $this->payload())->assertSessionHasNoErrors();
        }

        $this->post('/kesaksian', $this->payload())->assertTooManyRequests();
    }

    public function test_only_admin_and_superadmin_moderate(): void
    {
        foreach ([Role::Member, Role::Servant, Role::PrayerTeam] as $role) {
            $this->actingAs($this->user($role))->get(route('testimonies.index'))->assertForbidden();
        }

        $this->actingAs($this->user(Role::Admin))->get(route('testimonies.index'))->assertOk();
    }

    public function test_admin_approves_rejects_with_note_and_marks_revision(): void
    {
        $admin = $this->user(Role::Admin);
        $approve = $this->testimony('A');
        $reject = $this->testimony('B');
        $revise = $this->testimony('C');

        $this->actingAs($admin)->post(route('testimonies.moderate', $approve), ['status' => 'approved'])->assertRedirect();
        $this->actingAs($admin)->post(route('testimonies.moderate', $reject), ['status' => 'rejected'])->assertSessionHasErrors('note');
        $this->actingAs($admin)->post(route('testimonies.moderate', $reject), ['status' => 'rejected', 'note' => 'Spam']);
        $this->actingAs($admin)->post(route('testimonies.moderate', $revise), ['status' => 'revision', 'note' => 'Ada nama orang lain']);

        $this->assertSame(TestimonyStatus::Approved, $approve->refresh()->status);
        $this->assertSame($admin->id, $approve->moderated_by);
        $this->assertSame(TestimonyStatus::Rejected, $reject->refresh()->status);
        $this->assertSame('Spam', $reject->moderation_note);
        $this->assertSame(TestimonyStatus::Revision, $revise->refresh()->status);

        $this->get('/kesaksian')->assertInertia(fn (Assert $page) => $page
            ->has('testimonies.data', 1)
            ->missing('testimonies.data.0.note'));
    }

    public function test_admin_edits_wording_and_features_on_home(): void
    {
        $this->seed(LandingSeeder::class);
        $admin = $this->user(Role::Admin);
        $testimony = $this->testimony('Lama');
        $other = $this->testimony('Lain', TestimonyStatus::Approved);

        $this->actingAs($admin)->put(route('testimonies.update', $testimony), [
            'title' => 'Baru', 'body' => 'Isi yang sudah dirapikan.', 'category' => 'family', 'display_name' => 'Ibu R.',
        ])->assertRedirect();
        $this->assertSame('Baru', $testimony->refresh()->title);

        $this->actingAs($admin)->post(route('testimonies.feature', $testimony), ['featured' => true])->assertStatus(409);

        $this->actingAs($admin)->post(route('testimonies.moderate', $testimony), ['status' => 'approved']);
        $this->actingAs($admin)->post(route('testimonies.feature', $testimony), ['featured' => true])->assertRedirect();
        $this->assertTrue($testimony->refresh()->is_featured);

        $this->get(route('home'))->assertInertia(function (Assert $page) {
            $section = collect($page->toArray()['props']['sections'])->firstWhere('key', 'testimonies');

            $this->assertCount(1, $section['content']['items']);
            $this->assertSame('Baru', $section['content']['items'][0]['title']);
            $this->assertSame('Ibu R.', $section['content']['items'][0]['name']);
            $this->assertSame(route('testimonies.public.index'), $section['content']['url']);
        });

        $this->assertFalse($other->refresh()->is_featured);
    }

    public function test_home_falls_back_to_newest_approved_when_none_featured(): void
    {
        $this->seed(LandingSeeder::class);
        $this->testimony('Satu', TestimonyStatus::Approved);
        $this->testimony('Menunggu');

        $this->get(route('home'))->assertInertia(function (Assert $page) {
            $section = collect($page->toArray()['props']['sections'])->firstWhere('key', 'testimonies');

            $this->assertSame(['Satu'], array_column($section['content']['items'], 'title'));
        });
    }

    public function test_testimonies_of_another_branch_are_not_found(): void
    {
        $outside = $this->testimony('Lain', TestimonyStatus::Pending, $this->other);

        $this->actingAs($this->user(Role::Admin))
            ->post(route('testimonies.moderate', $outside), ['status' => 'approved'])
            ->assertNotFound();
    }

    public function test_seeder_adds_samples_once(): void
    {
        Church::query()->whereKey($this->other->id)->delete();

        $this->seed(TestimoniesSeeder::class);
        $this->seed(TestimoniesSeeder::class);

        $this->assertSame(5, Testimony::query()->withoutGlobalScopes()->count());
        $this->assertSame(2, Testimony::query()->withoutGlobalScopes()->where('is_featured', true)->count());
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'title' => 'Tuhan menolong kami',
            'body' => 'Kami mengalami pertolongan Tuhan saat keluarga sakit.',
            'category' => 'healing',
            'display_name' => 'Ibu R.',
            'website' => '',
            ...$overrides,
        ];
    }

    private function testimony(string $title, TestimonyStatus $status = TestimonyStatus::Pending, ?Church $church = null): Testimony
    {
        $testimony = new Testimony(['title' => $title, 'body' => 'Isi kesaksian.', 'category' => 'other']);
        $testimony->church_id = ($church ?? $this->elim)->id;
        $testimony->status = $status;
        $testimony->moderated_at = $status === TestimonyStatus::Approved ? now() : null;
        $testimony->save();

        return $testimony;
    }

    private function user(Role $role): User
    {
        $user = User::factory()->create(['church_id' => $this->elim->id]);
        $user->assignRole($role->value);

        return $user;
    }
}
