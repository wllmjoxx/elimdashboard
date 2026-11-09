<?php

namespace App\Modules\Gallery\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Gallery\Models\Album;
use App\Modules\Gallery\Models\AlbumPhoto;
use App\Modules\Gallery\Support\GalleryFeed;
use App\Modules\Gallery\Support\PhotoProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Gallery admin (PRD 4.8): albums, photo upload with automatic
 * compression, captions, order and visibility.
 */
class GalleryController extends Controller
{
    public function __construct(private readonly GalleryFeed $feed) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Album::class);

        $albums = Album::query()
            ->with('cover')
            ->withCount(['photos', 'activePhotos'])
            ->orderByDesc('event_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Album $album) => [
                ...$this->feed->album($album, $album->photos_count),
                'isActive' => $album->is_active,
                'activePhotoCount' => $album->active_photos_count,
            ]);

        return Inertia::render('gallery/index', [
            'albums' => $albums,
            'canManage' => $request->user()->can('manage', Album::class),
            'flash' => $request->session()->get('status'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage', Album::class);

        $album = Album::query()->create($this->validateAlbum($request));

        return to_route('gallery.show', $album)->with('status', "Album \"{$album->title}\" dibuat. Sekarang unggah fotonya.");
    }

    public function show(Request $request, Album $album): Response
    {
        Gate::authorize('viewAny', Album::class);

        $user = $request->user();
        $upload = config('modules.gallery.upload');

        return Inertia::render('gallery/show', [
            'album' => [
                ...$this->feed->album($album),
                'isActive' => $album->is_active,
            ],
            'photos' => $album->photos()->with('album')->get()->map(fn (AlbumPhoto $photo) => [
                ...$this->feed->photo($photo),
                'isActive' => $photo->is_active,
                'canEdit' => $user->can('editPhoto', [$album, $photo]),
            ])->values(),
            'can' => [
                'manage' => $user->can('manage', Album::class),
                'upload' => $user->can('upload', $album),
            ],
            'uploadRules' => [
                'accept' => implode(',', array_map(fn (string $mime) => '.'.$mime, $upload['mimes'])),
                'maxMb' => intdiv($upload['max_kb'], 1024),
                'minSide' => $upload['min_side'],
                'maxFiles' => $upload['max_files'],
                'compressed' => app(PhotoProcessor::class)->canProcess(),
                'maxSide' => config('modules.gallery.compress.max_side'),
            ],
            'flash' => $request->session()->get('status'),
        ]);
    }

    public function update(Request $request, Album $album): RedirectResponse
    {
        Gate::authorize('manage', Album::class);

        $album->update($this->validateAlbum($request));

        return back()->with('status', 'Album tersimpan.');
    }

    public function destroy(Album $album): RedirectResponse
    {
        Gate::authorize('manage', Album::class);

        // Delete photo by photo so their files are removed too.
        $album->photos()->get()->each->delete();
        $album->delete();

        return to_route('gallery.index')->with('status', "Album \"{$album->title}\" dihapus.");
    }

    public function upload(Request $request, Album $album, PhotoProcessor $processor): RedirectResponse
    {
        Gate::authorize('upload', $album);

        $rules = config('modules.gallery.upload');

        $request->validate([
            'photos' => ['required', 'array', 'max:'.$rules['max_files']],
            'photos.*' => [
                'image',
                'mimes:'.implode(',', $rules['mimes']),
                'max:'.$rules['max_kb'],
                Rule::dimensions()->minWidth($rules['min_side'])->minHeight($rules['min_side']),
            ],
        ], [
            'photos.*.dimensions' => "Setiap foto minimal {$rules['min_side']} × {$rules['min_side']} px.",
            'photos.*.max' => 'Ukuran setiap foto maksimal '.intdiv($rules['max_kb'], 1024).' MB.',
            'photos.*.mimes' => 'Format foto harus JPG, PNG, atau WebP.',
            'photos.max' => "Maksimal {$rules['max_files']} foto sekali unggah.",
        ], ['photos' => 'foto']);

        $order = (int) $album->photos()->max('sort_order');

        foreach ($request->file('photos') as $file) {
            $stored = $processor->store($file, (string) $album->id);

            $photo = new AlbumPhoto;
            $photo->forceFill([
                ...$stored,
                'album_id' => $album->id,
                'sort_order' => ++$order,
                'uploaded_by' => $request->user()->id,
            ])->save();
        }

        $count = count($request->file('photos'));

        return back()->with('status', "{$count} foto diunggah.");
    }

    public function updatePhoto(Request $request, Album $album, AlbumPhoto $photo): RedirectResponse
    {
        Gate::authorize('editPhoto', [$album, $photo]);

        $photo->update($request->validate([
            'caption' => ['nullable', 'string', 'max:200'],
            'is_active' => ['boolean'],
        ]));

        return back()->with('status', 'Foto tersimpan.');
    }

    /**
     * Swap a photo with its neighbour to change the order.
     */
    public function movePhoto(Request $request, Album $album, AlbumPhoto $photo): RedirectResponse
    {
        Gate::authorize('manage', Album::class);

        $direction = $request->validate(['direction' => ['required', Rule::in(['up', 'down'])]])['direction'];
        $ordered = $album->photos()->get()->values()->all();
        $index = collect($ordered)->search(fn (AlbumPhoto $item) => $item->is($photo));
        $target = $direction === 'up' ? $index - 1 : $index + 1;

        if ($index === false || ! isset($ordered[$target])) {
            return back();
        }

        [$ordered[$index], $ordered[$target]] = [$ordered[$target], $ordered[$index]];

        // Renumber the whole album so the order stays gapless.
        DB::transaction(function () use ($ordered) {
            foreach ($ordered as $position => $item) {
                $item->forceFill(['sort_order' => ($position + 1) * 10])->saveQuietly();
            }
        });

        return back();
    }

    public function destroyPhoto(Album $album, AlbumPhoto $photo): RedirectResponse
    {
        Gate::authorize('editPhoto', [$album, $photo]);

        $photo->delete();

        return back()->with('status', 'Foto dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateAlbum(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'event_date' => ['nullable', 'date'],
            'is_active' => ['boolean'],
        ], [], [
            'title' => 'judul album',
            'description' => 'keterangan',
            'event_date' => 'tanggal kegiatan',
        ]);
    }
}
