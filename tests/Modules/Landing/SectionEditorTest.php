<?php

namespace Tests\Modules\Landing;

use App\Models\User;
use App\Modules\Core\Actions\SyncRolesAndPermissions;
use App\Modules\Core\Enums\Role;
use App\Modules\Landing\Database\Seeders\LandingSeeder;
use App\Modules\Landing\Models\SiteSection;
use App\Modules\Landing\Support\SectionEditor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SectionEditorTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        app(SyncRolesAndPermissions::class)->handle();
        $this->seed(LandingSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole(Role::Admin->value);
    }

    public function test_layout_page_lists_sections_in_order_with_editors(): void
    {
        $this->actingAs($this->admin)
            ->get(route('landing.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('landing/index')
                ->has('sections', count(SiteSection::KEYS))
                ->where('sections.0.key', 'hero')
                ->where('sections.0.editor', 'section')
                ->where('sections.1.key', 'segment_1')
                ->where('sections.1.editor', 'segment')
                ->where('sections.0.label', SectionEditor::label('hero')));
    }

    public function test_only_landing_managers_reach_the_editor(): void
    {
        $member = User::factory()->create();
        $member->assignRole(Role::Member->value);

        $this->actingAs($member)->get(route('landing.sections.edit', 'hero'))->assertForbidden();
        $this->actingAs($member)->put(route('landing.sections.update', 'mission'), ['title' => 'X', 'text' => 'Y'])->assertForbidden();
    }

    public function test_segments_and_unknown_keys_have_no_text_editor(): void
    {
        $this->actingAs($this->admin)->get('/admin/landing/sections/segment_1')->assertNotFound();
        $this->actingAs($this->admin)->get('/admin/landing/sections/nope')->assertNotFound();
    }

    public function test_hero_slides_and_buttons_are_saved_and_shown_on_home(): void
    {
        $this->actingAs($this->admin)
            ->put(route('landing.sections.update', 'hero'), [
                'slides' => [
                    ['eyebrow' => 'Tema 2027', 'title' => 'Bangkit dan Bersinar', 'text' => '', 'verse' => 'Bangkitlah, menjadi teranglah.', 'reference' => 'Yesaya 60:1'],
                    ['title' => 'Selamat datang'],
                ],
                'primary_cta' => ['label' => 'Jadwal', 'href' => '/jadwal'],
                'secondary_cta' => ['label' => '', 'href' => ''],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->get(route('home'))->assertInertia(fn (Assert $page) => $page
            ->where('sections.0.content.slides.0.title', 'Bangkit dan Bersinar')
            ->where('sections.0.content.slides.0.reference', 'Yesaya 60:1')
            ->missing('sections.0.content.slides.0.text')
            ->has('sections.0.content.slides', 2)
            ->where('sections.0.content.primary_cta.href', '/jadwal')
            ->where('sections.0.content.secondary_cta', null));
    }

    public function test_hero_validation(): void
    {
        $this->actingAs($this->admin)
            ->put(route('landing.sections.update', 'hero'), [
                'slides' => [['title' => '', 'verse' => 'Ayat tanpa rujukan']],
                'primary_cta' => ['label' => 'Klik', 'href' => 'javascript:alert(1)'],
            ])
            ->assertSessionHasErrors(['slides.0.title', 'slides.0.reference', 'primary_cta.href']);

        $this->actingAs($this->admin)
            ->put(route('landing.sections.update', 'hero'), ['slides' => array_fill(0, 6, ['title' => 'A'])])
            ->assertSessionHasErrors('slides');
    }

    public function test_about_paragraphs_and_milestones_show_on_the_tentang_page(): void
    {
        $this->actingAs($this->admin)
            ->put(route('landing.sections.update', 'about'), [
                'title' => 'Sejarah Kami',
                'paragraphs' => ['Paragraf satu.', 'Paragraf dua.'],
                'milestones' => [['year' => '1923', 'text' => 'GGP berdiri di Bandung']],
            ])
            ->assertSessionHasNoErrors();

        $this->get('/tentang')->assertInertia(fn (Assert $page) => $page
            ->where('sections.0.content.title', 'Sejarah Kami')
            ->where('sections.0.content.paragraphs', ['Paragraf satu.', 'Paragraf dua.'])
            ->where('sections.0.content.milestones.0.year', '1923'));
    }

    public function test_simple_sections_keep_their_other_settings(): void
    {
        $gallery = SiteSection::query()->where('key', 'gallery')->firstOrFail();
        $gallery->update(['content' => [...$gallery->content, 'layout' => 'masonry']]);

        $this->actingAs($this->admin)
            ->put(route('landing.sections.update', 'gallery'), ['title' => 'Momen Kegiatan', 'layout' => 'grid'])
            ->assertSessionHasNoErrors();

        $content = $gallery->refresh()->content;
        $this->assertSame('Momen Kegiatan', $content['title']);
        $this->assertSame('masonry', $content['layout']);
        $this->assertArrayHasKey('items', $content);
    }

    public function test_contact_is_validated_and_saved(): void
    {
        $this->actingAs($this->admin)
            ->put(route('landing.sections.update', 'contact'), ['title' => 'Kontak', 'address' => '', 'whatsapp' => 'abc'])
            ->assertSessionHasErrors(['address', 'whatsapp']);

        $this->actingAs($this->admin)
            ->put(route('landing.sections.update', 'contact'), [
                'title' => 'Kontak',
                'address' => 'Jl. Baru 1, Ciputat',
                'whatsapp' => '0812 0000 1111',
                'hours' => 'Setiap hari',
                'maps_query' => 'GGP Elim Ciputat',
            ])
            ->assertSessionHasNoErrors();

        $this->get('/kontak')->assertInertia(fn (Assert $page) => $page
            ->where('sections.0.content.address', 'Jl. Baru 1, Ciputat')
            ->where('sections.0.content.whatsapp', '0812 0000 1111'));
    }

    public function test_sections_can_be_reordered_and_hidden(): void
    {
        $keys = SiteSection::query()->orderBy('sort_order')->pluck('key')->all();
        $reordered = array_merge(['testimonies'], array_values(array_diff($keys, ['testimonies'])));

        $this->actingAs($this->admin)->post(route('landing.sections.order'), ['keys' => $reordered])->assertRedirect();
        $this->actingAs($this->admin)->post(route('landing.sections.visibility', 'gallery'), ['is_active' => false])->assertRedirect();

        $this->get(route('home'))->assertInertia(function (Assert $page) {
            $homeKeys = array_column($page->toArray()['props']['sections'], 'key');

            $this->assertSame('testimonies', $homeKeys[0]);
            $this->assertNotContains('gallery', $homeKeys);
        });

        $this->actingAs($this->admin)->post(route('landing.sections.order'), ['keys' => ['bogus']])->assertSessionHasErrors('keys.0');
    }

    public function test_seeder_never_overwrites_admin_edits(): void
    {
        $this->actingAs($this->admin)->put(route('landing.sections.update', 'mission'), ['title' => 'Misi', 'text' => 'Misi baru.']);

        $this->seed(LandingSeeder::class);

        $this->assertSame('Misi baru.', SiteSection::query()->where('key', 'mission')->firstOrFail()->content['text']);
    }
}
