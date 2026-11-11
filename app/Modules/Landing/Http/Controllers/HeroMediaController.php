<?php

namespace App\Modules\Landing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Landing\Http\Requests\StoreHeroVideoRequest;
use App\Modules\Landing\Http\Requests\UpdateLiveRequest;
use App\Modules\Landing\Models\HeroVideo;
use App\Modules\Landing\Models\SiteSection;
use App\Modules\Landing\Support\LiveStream;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin › Landing Page › Video & Live: the videos that play in the first
 * homepage segment and the live stream that replaces them while on air.
 */
class HeroMediaController extends Controller
{
    public function index(Request $request): Response
    {
        $config = config('modules.landing.hero_video');
        $live = $this->hero()->content['live'] ?? [];

        return Inertia::render('landing/media', [
            'videos' => HeroVideo::query()->ordered()->get()->map(fn (HeroVideo $video) => [
                'id' => $video->id,
                'title' => $video->title,
                'url' => $video->url(),
                'posterUrl' => $video->posterUrl(),
                'sizeMb' => round($video->size / 1048576, 1),
                'isActive' => $video->is_active,
            ])->values(),
            'live' => [
                'url' => $live['url'] ?? '',
                'title' => $live['title'] ?? '',
                'isLive' => (bool) ($live['is_live'] ?? false),
                'isOn' => LiveStream::isOn($live),
                'embeddable' => LiveStream::embedUrl($live['url'] ?? null) !== null,
                'startedAt' => isset($live['started_at'])
                    ? Carbon::parse($live['started_at'])->locale('id')->translatedFormat('l, j F Y H.i')
                    : null,
                'autoOffHours' => (int) config('modules.landing.live.auto_off_hours'),
            ],
            'videoMode' => $this->hero()->content['video_mode'] ?? 'frame',
            'rules' => [
                'maxMb' => (int) round($config['max_kb'] / 1024),
                'maxCount' => (int) $config['max_count'],
                'recommended' => $config['recommended'],
            ],
            'flash' => $request->session()->get('status'),
        ]);
    }

    public function updateLive(UpdateLiveRequest $request): RedirectResponse
    {
        $hero = $this->hero();
        $content = $hero->content;
        $previous = $content['live'] ?? [];
        $isLive = $request->boolean('is_live');

        // Keep the start time while it stays live, so the automatic cut-off
        // counts from when it went on air.
        $startedAt = null;

        if ($isLive) {
            $startedAt = LiveStream::isOn($previous) && isset($previous['started_at'])
                ? $previous['started_at']
                : now()->toIso8601String();
        }

        $content['live'] = [
            'url' => $request->validated('url'),
            'title' => $request->validated('title'),
            'is_live' => $isLive,
            'started_at' => $startedAt,
        ];
        $hero->content = $content;
        $hero->save();

        return back()->with('status', $isLive ? 'Live streaming tampil di beranda.' : 'Pengaturan live disimpan.');
    }

    /**
     * How the videos show: in a frame under the welcome ("frame") or full
     * width behind it, softly blurred ("background"). A live stream always
     * plays in the frame.
     */
    public function updateMode(Request $request): RedirectResponse
    {
        $mode = $request->validate([
            'video_mode' => ['required', Rule::in(['frame', 'background'])],
        ], [], ['video_mode' => 'tampilan video'])['video_mode'];

        $hero = $this->hero();
        $hero->content = [...$hero->content, 'video_mode' => $mode];
        $hero->save();

        return back()->with('status', $mode === 'background' ? 'Video tampil sebagai latar beranda.' : 'Video tampil dalam bingkai.');
    }

    public function store(StoreHeroVideoRequest $request): RedirectResponse
    {
        $config = config('modules.landing.hero_video');
        $file = $request->file('video');

        HeroVideo::query()->create([
            'title' => $request->validated('title'),
            'path' => $file->store($config['directory'], $config['disk']),
            'poster_path' => $request->file('poster')?->store($config['directory'].'/posters', $config['disk']),
            'mime' => (string) $file->getMimeType(),
            'size' => (int) $file->getSize(),
            'sort_order' => (int) HeroVideo::query()->max('sort_order') + 10,
        ]);

        return back()->with('status', 'Video ditambahkan.');
    }

    public function update(Request $request, HeroVideo $video): RedirectResponse
    {
        $video->update($request->validate([
            'title' => ['nullable', 'string', 'max:120'],
            'is_active' => ['required', 'boolean'],
        ], [], ['title' => 'judul']));

        return back()->with('status', $video->is_active ? 'Video ditampilkan.' : 'Video disembunyikan.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $ids = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:hero_videos,id'],
        ])['ids'];

        DB::transaction(function () use ($ids) {
            foreach (array_values(array_unique($ids)) as $position => $id) {
                HeroVideo::query()->whereKey($id)->update(['sort_order' => ($position + 1) * 10]);
            }
        });

        return back()->with('status', 'Urutan video disimpan.');
    }

    public function destroy(HeroVideo $video): RedirectResponse
    {
        $video->deleteWithFiles();

        return back()->with('status', 'Video dihapus.');
    }

    /**
     * The hero section holds the live settings; created when missing.
     */
    private function hero(): SiteSection
    {
        return SiteSection::query()->firstOrCreate(
            ['church_id' => null, 'key' => 'hero'],
            ['content' => ['slides' => []], 'is_active' => true, 'sort_order' => 0],
        );
    }
}
