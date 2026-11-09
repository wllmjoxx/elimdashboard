<?php

namespace Tests\Modules\Gallery;

use App\Models\User;
use App\Modules\Core\Actions\SyncRolesAndPermissions;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\Church;
use App\Modules\Gallery\Database\Seeders\GallerySeeder;
use App\Modules\Gallery\Models\Album;
use App\Modules\Gallery\Models\AlbumPhoto;
use App\Modules\Gallery\Support\PhotoProcessor;
use App\Modules\Landing\Database\Seeders\LandingSeeder;
use App\Modules\Landing\Models\SiteSection;
use App\Support\Modules\ModuleRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GalleryModuleTest extends TestCase
{
    use RefreshDatabase;

    private Church $elim;

    private Church $other;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        app(SyncRolesAndPermissions::class)->handle();
        $this->elim = Church::query()->create(['name' => 'GGP Elim', 'slug' => 'ggp-elim']);
        $this->other = Church::query()->create(['name' => 'GGP Lain', 'slug' => 'ggp-lain']);
    }

    public function test_module_is_registered(): void
    {
        $this->assertNotNull(app(ModuleRegistry::class)->find('gallery'));
    }

    public function test_admin_creates_an_album_and_servant_cannot(): void
    {
        $this->actingAs($this->user(Role::Servant))
            ->post(route('gallery.store'), ['title' => 'Paskah'])
            ->assertForbidden();

        $this->actingAs($this->user(Role::Admin))
            ->post(route('gallery.store'), ['title' => 'Paskah 2026', 'event_date' => '2026-04-05'])
            ->assertRedirect();

        $album = Album::query()->withoutGlobalScopes()->firstOrFail();
        $this->assertSame('paskah-2026', $album->slug);
        $this->assertSame($this->elim->id, $album->church_id);
    }

    public function test_servant_uploads_photos_and_edits_only_own(): void
    {
        $album = $this->album('Ibadah');
        $servant = $this->user(Role::Servant);

        $this->actingAs($servant)
            ->post(route('gallery.photos.store', $album), ['photos' => [self::png(1200, 800), self::png(900, 900)]])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $photos = $album->photos()->get();
        $this->assertCount(2, $photos);
        $this->assertSame([1, 2], $photos->pluck('sort_order')->all());
        $this->assertSame($servant->id, $photos[0]->uploaded_by);
        Storage::disk('public')->assertExists($photos[0]->image);

        $others = $this->photo($album, uploadedBy: $this->user(Role::Servant)->id);

        $this->actingAs($servant)->put(route('gallery.photos.update', [$album, $photos[0]]), ['caption' => 'Pujian'])->assertRedirect();
        $this->assertSame('Pujian', $photos[0]->refresh()->caption);
        $this->actingAs($servant)->put(route('gallery.photos.update', [$album, $others]), ['caption' => 'X'])->assertForbidden();
        $this->actingAs($servant)->post(route('gallery.photos.move', [$album, $photos[0]]), ['direction' => 'down'])->assertForbidden();
    }

    public function test_upload_rules_are_enforced(): void
    {
        $album = $this->album('Ibadah');
        $admin = $this->user(Role::Admin);

        $this->actingAs($admin)
            ->post(route('gallery.photos.store', $album), ['photos' => [self::png(400, 300)]])
            ->assertSessionHasErrors('photos.0');

        $this->actingAs($admin)
            ->post(route('gallery.photos.store', $album), ['photos' => [UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')]])
            ->assertSessionHasErrors('photos.0');

        $this->actingAs($admin)
            ->post(route('gallery.photos.store', $album), ['photos' => array_map(fn () => self::png(800, 800), range(1, 21))])
            ->assertSessionHasErrors('photos');

        $this->assertSame(0, $album->photos()->count());
    }

    public function test_admin_reorders_hides_and_deletes_photos_and_files(): void
    {
        $album = $this->album('Ibadah');
        $admin = $this->user(Role::Admin);
        $first = $this->photo($album, 10);
        $second = $this->photo($album, 20);

        $this->actingAs($admin)->post(route('gallery.photos.move', [$album, $second]), ['direction' => 'up'])->assertRedirect();
        $this->assertSame([$second->id, $first->id], $album->photos()->pluck('id')->all());

        $this->actingAs($admin)->put(route('gallery.photos.update', [$album, $first]), ['caption' => null, 'is_active' => false]);
        $this->assertFalse($first->refresh()->is_active);

        Storage::disk('public')->put($second->image, 'x');
        $this->actingAs($admin)->delete(route('gallery.photos.destroy', [$album, $second]))->assertRedirect();
        $this->assertModelMissing($second);
        Storage::disk('public')->assertMissing($second->image);
    }

    public function test_photo_must_belong_to_the_album_in_the_url(): void
    {
        $album = $this->album('A');
        $otherAlbum = $this->album('B');
        $photo = $this->photo($otherAlbum);

        $this->actingAs($this->user(Role::Admin))
            ->delete(route('gallery.photos.destroy', [$album, $photo]))
            ->assertNotFound();
    }

    public function test_albums_of_another_branch_are_not_found(): void
    {
        $outside = $this->album('Lain', $this->other);

        $this->actingAs($this->user(Role::Admin))->get(route('gallery.show', $outside))->assertNotFound();
    }

    public function test_public_pages_show_only_active_albums_and_photos(): void
    {
        $album = $this->album('Natal');
        $this->photo($album, caption: 'Paduan suara');
        $this->photo($album, active: false);
        $hidden = $this->album('Tersembunyi');
        $hidden->update(['is_active' => false]);
        $this->photo($hidden);
        $this->album('Kosong');

        $this->get('/galeri')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('gallery/public/index')
                ->has('albums.data', 1)
                ->where('albums.data.0.title', 'Natal')
                ->where('albums.data.0.photoCount', 1));

        $this->get('/galeri/natal')
            ->assertInertia(fn (Assert $page) => $page
                ->component('gallery/public/show')
                ->has('photos', 1)
                ->where('photos.0.caption', 'Paduan suara'));

        $this->get('/galeri/tersembunyi')->assertNotFound();
    }

    public function test_home_gallery_uses_the_chosen_album_and_layout(): void
    {
        $this->seed(LandingSeeder::class);
        $admin = $this->user(Role::Admin);
        $newest = $this->album('Terbaru', eventDate: '2026-11-01');
        $this->photo($newest, caption: 'Baru');
        $chosen = $this->album('Pilihan', eventDate: '2026-01-01');
        $this->photo($chosen, caption: 'Dipilih');

        $section = fn () => collect($this->get(route('home'))->viewData('page')['props']['sections'])->firstWhere('key', 'gallery')['content'];

        $this->assertSame('Baru', $section()['items'][0]['caption']);
        $this->assertFalse($section()['is_sample']);
        $this->assertSame('grid', $section()['layout']);

        $this->actingAs($admin)
            ->post(route('landing.gallery.update'), ['album_id' => $chosen->id, 'layout' => 'masonry'])
            ->assertRedirect();

        $this->assertSame('Dipilih', $section()['items'][0]['caption']);
        $this->assertSame('masonry', $section()['layout']);
        $this->assertSame(route('gallery.public.show', 'pilihan'), $section()['url']);
        $this->assertArrayNotHasKey('album_id', $section());

        $this->seed(LandingSeeder::class);
        $this->assertSame($chosen->id, SiteSection::query()->where('key', 'gallery')->firstOrFail()->content['album_id']);
    }

    public function test_home_gallery_settings_need_landing_manage_and_an_own_album(): void
    {
        $this->seed(LandingSeeder::class);
        $outside = $this->album('Lain', $this->other);

        $this->actingAs($this->user(Role::Servant))
            ->post(route('landing.gallery.update'), ['album_id' => null, 'layout' => 'grid'])
            ->assertForbidden();

        $this->actingAs($this->user(Role::Admin))
            ->post(route('landing.gallery.update'), ['album_id' => $outside->id, 'layout' => 'grid'])
            ->assertSessionHasErrors('album_id');

        $this->actingAs($this->user(Role::Admin))
            ->post(route('landing.gallery.update'), ['album_id' => null, 'layout' => 'mosaic'])
            ->assertSessionHasErrors('layout');
    }

    public function test_home_gallery_falls_back_to_samples_without_photos(): void
    {
        $this->seed(LandingSeeder::class);

        $section = collect($this->get(route('home'))->viewData('page')['props']['sections'])->firstWhere('key', 'gallery')['content'];

        $this->assertTrue($section['is_sample']);
        $this->assertCount(5, $section['items']);
    }

    public function test_processor_keeps_the_original_when_it_cannot_decode(): void
    {
        $stored = app(PhotoProcessor::class)->store(self::png(1200, 800), '1');

        $this->assertSame($stored['image'], $stored['thumbnail']);
        $this->assertSame(1200, $stored['width']);
        Storage::disk('public')->assertExists($stored['image']);
    }

    public function test_processor_compresses_real_images_when_gd_is_available(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is not installed.');
        }

        $image = imagecreatetruecolor(3000, 2000);
        ob_start();
        imagejpeg($image, null, 95);
        $file = UploadedFile::fake()->createWithContent('big.jpg', (string) ob_get_clean());

        $stored = app(PhotoProcessor::class)->store($file, '1');

        $this->assertNotSame($stored['image'], $stored['thumbnail']);
        $this->assertSame(1920, $stored['width']);
        $this->assertSame(1280, $stored['height']);
        [$thumbWidth] = getimagesizefromstring(Storage::disk('public')->get($stored['thumbnail']));
        $this->assertSame(600, $thumbWidth);
    }

    public function test_seeder_adds_albums_once(): void
    {
        Church::query()->whereKey($this->other->id)->delete();

        $this->seed(GallerySeeder::class);
        $this->seed(GallerySeeder::class);

        $this->assertSame(4, Album::query()->withoutGlobalScopes()->count());
    }

    private function album(string $title, ?Church $church = null, ?string $eventDate = null): Album
    {
        $album = new Album(['title' => $title, 'event_date' => $eventDate]);
        $album->church_id = ($church ?? $this->elim)->id;
        $album->save();

        return $album;
    }

    private function photo(Album $album, int $order = 0, ?int $uploadedBy = null, ?string $caption = null, bool $active = true): AlbumPhoto
    {
        $photo = new AlbumPhoto;
        $photo->forceFill([
            'album_id' => $album->id,
            'image' => "gallery/{$album->id}/photo-".uniqid().'.jpg',
            'thumbnail' => "gallery/{$album->id}/thumb-".uniqid().'.jpg',
            'caption' => $caption,
            'sort_order' => $order,
            'is_active' => $active,
            'uploaded_by' => $uploadedBy,
        ])->save();

        return $photo;
    }

    private function user(Role $role): User
    {
        $user = User::factory()->create(['church_id' => $this->elim->id]);
        $user->assignRole($role->value);

        return $user;
    }

    /**
     * A PNG with the given pixel size; only the header is real, so GD cannot
     * decode it and the processor keeps the original.
     */
    private static function png(int $width, int $height): File
    {
        $chunk = fn (string $type, string $data) => pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));

        $bytes = "\x89PNG\r\n\x1a\n"
            .$chunk('IHDR', pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0))
            .$chunk('IDAT', (string) gzcompress(''))
            .$chunk('IEND', '');

        return UploadedFile::fake()->createWithContent('photo.png', $bytes);
    }
}
