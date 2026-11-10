<?php

namespace Tests\Modules\Landing;

use App\Models\User;
use App\Modules\Core\Actions\SyncRolesAndPermissions;
use App\Modules\Core\Enums\Role;
use App\Modules\Landing\Database\Seeders\LandingSeeder;
use App\Modules\Landing\Models\SiteSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SegmentAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed(LandingSeeder::class);
        app(SyncRolesAndPermissions::class)->handle();
    }

    public function test_roles_without_landing_manage_cannot_edit(): void
    {
        $member = User::factory()->create();
        $member->assignRole(Role::Member->value);

        $this->actingAs($member)->get(route('landing.segments.edit'))->assertForbidden();
        $this->actingAs($member)
            ->post(route('landing.segments.update', 'segment_1'), $this->payload())
            ->assertForbidden();
    }

    public function test_admin_page_lists_three_segments_and_the_upload_rules(): void
    {
        $this->actingAs($this->admin())
            ->get(route('landing.segments.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('landing/segments')
                ->has('segments', 3)
                ->where('segments.0.key', 'segment_1')
                ->where('segments.0.label', 'Segmen 1')
                ->where('segments.0.title', 'Rumah Kasih Karunia')
                ->where('imageRules.maxMb', 3)
                ->where('imageRules.minWidth', 1600)
                ->where('imageRules.minHeight', 900));
    }

    public function test_guests_cannot_update_a_segment(): void
    {
        $this->post(route('landing.segments.update', 'segment_1'), $this->payload())
            ->assertRedirect(route('login'));
    }

    public function test_unknown_segments_are_not_routed(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/landing/segments/segment_9', $this->payload())
            ->assertNotFound();
    }

    public function test_segment_text_and_image_can_be_saved(): void
    {
        $this->actingAs($this->admin())
            ->post(route('landing.segments.update', 'segment_2'), $this->payload([
                'title' => 'Doa Malam',
                'align' => 'center',
                'image' => self::png(1920, 1080)->size(800),
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $content = $this->segment('segment_2');

        $this->assertSame('Doa Malam', $content['title']);
        $this->assertSame('center', $content['align']);
        Storage::disk('public')->assertExists($content['image']);

        $this->get(route('home'))->assertInertia(fn (Assert $page) => $page
            ->where('sections.4.key', 'segment_2')
            ->where('sections.4.content.image_url', Storage::disk('public')->url($content['image'])));
    }

    public function test_replacing_or_removing_an_image_deletes_the_old_file(): void
    {
        $user = $this->admin();
        $url = route('landing.segments.update', 'segment_1');

        $this->actingAs($user)->post($url, $this->payload(['image' => self::png(1920, 1080)]));
        $first = $this->segment('segment_1')['image'];

        $this->actingAs($user)->post($url, $this->payload(['image' => self::png(2400, 1200)]));
        $second = $this->segment('segment_1')['image'];

        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);

        $this->actingAs($user)->post($url, $this->payload(['remove_image' => true]));

        Storage::disk('public')->assertMissing($second);
        $this->assertNull($this->segment('segment_1')['image']);
    }

    public function test_saving_without_a_new_image_keeps_the_current_one(): void
    {
        $user = $this->admin();
        $url = route('landing.segments.update', 'segment_1');

        $this->actingAs($user)->post($url, $this->payload(['image' => self::png(1920, 1080)]));
        $image = $this->segment('segment_1')['image'];

        $this->actingAs($user)->post($url, $this->payload(['title' => 'Judul baru']));

        $this->assertSame($image, $this->segment('segment_1')['image']);
        Storage::disk('public')->assertExists($image);
    }

    /**
     * @return array<string, array{0: string, 1: int, 2: int, 3: int}>
     */
    public static function invalidImages(): array
    {
        return [
            'too small' => ['png', 1200, 700, 100],
            'portrait' => ['png', 1800, 2400, 100],
            'square-ish' => ['png', 2000, 1600, 100],
            'too large file' => ['png', 1920, 1080, 4000],
            'not an image' => ['pdf', 0, 0, 100],
            'gif' => ['gif', 1920, 1080, 100],
        ];
    }

    #[DataProvider('invalidImages')]
    public function test_images_outside_the_rules_are_refused(string $type, int $width, int $height, int $kilobytes): void
    {
        $file = match ($type) {
            'png' => self::png($width, $height),
            'gif' => UploadedFile::fake()->createWithContent('anim.gif', 'GIF89a'.pack('vv', $width, $height).str_repeat("\0", 32)),
            default => UploadedFile::fake()->create('notes.pdf', 1, 'application/pdf'),
        };

        $this->actingAs($this->admin())
            ->post(route('landing.segments.update', 'segment_1'), $this->payload(['image' => $file->size($kilobytes)]))
            ->assertSessionHasErrors('image');

        $this->assertNull($this->segment('segment_1')['image']);
    }

    public function test_button_needs_both_label_and_a_safe_link(): void
    {
        $user = $this->admin();
        $url = route('landing.segments.update', 'segment_1');

        $this->actingAs($user)
            ->post($url, $this->payload(['button_label' => 'Klik', 'button_href' => 'javascript:alert(1)']))
            ->assertSessionHasErrors('button_href');

        $this->actingAs($user)
            ->post($url, $this->payload(['button_label' => '', 'button_href' => '/jadwal']))
            ->assertSessionHasErrors('button_label');
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::Admin->value);

        return $admin;
    }

    /**
     * @return array<string, mixed>
     */
    private function segment(string $key): array
    {
        return SiteSection::query()->where('key', $key)->firstOrFail()->content;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'eyebrow' => 'Label',
            'title' => 'Judul',
            'text' => 'Teks segmen.',
            'button_label' => 'Lihat',
            'button_href' => '/jadwal',
            'align' => 'left',
            'is_active' => true,
            ...$overrides,
        ];
    }

    /**
     * A PNG with the given pixel size. Only the header matters to the upload
     * rules, so the tests do not need the GD extension.
     */
    private static function png(int $width, int $height): File
    {
        $chunk = fn (string $type, string $data) => pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));

        $bytes = "\x89PNG\r\n\x1a\n"
            .$chunk('IHDR', pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0))
            .$chunk('IDAT', (string) gzcompress(''))
            .$chunk('IEND', '');

        return UploadedFile::fake()->createWithContent('banner.png', $bytes);
    }
}
