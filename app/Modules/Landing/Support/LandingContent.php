<?php

namespace App\Modules\Landing\Support;

use App\Modules\Devotionals\Support\DevotionalFeed;
use App\Modules\Events\Support\EventFeed;
use App\Modules\Gallery\Support\GalleryFeed;
use App\Modules\Landing\Models\HeroVideo;
use App\Modules\Landing\Models\ServiceSchedule;
use App\Modules\Landing\Models\SiteSection;
use App\Modules\Testimonies\Support\TestimonyFeed;
use Illuminate\Support\Facades\Storage;

/**
 * Turns landing data into the props the public pages render.
 */
class LandingContent
{
    public function __construct(
        private readonly DevotionalFeed $devotionals,
        private readonly TestimonyFeed $testimonies,
        private readonly GalleryFeed $gallery,
        private readonly EventFeed $events,
    ) {}

    /**
     * Visible sections with the given keys, in admin-defined order.
     *
     * @param  list<string>  $keys
     * @return list<array{key: string, content: array<string, mixed>}>
     */
    public function sections(array $keys): array
    {
        return SiteSection::query()->visible()->whereIn('key', $keys)->get()
            ->map(fn (SiteSection $section) => [
                'key' => $section->key,
                'content' => $this->present($section),
            ])
            ->values()
            ->all();
    }

    /**
     * Worship schedule grouped by category, skipping empty categories.
     *
     * @return list<array<string, mixed>>
     */
    public function scheduleGroups(): array
    {
        $schedules = ServiceSchedule::query()->visible()->get()->groupBy('category');

        return collect(ServiceSchedule::CATEGORIES)
            ->filter(fn (string $label, string $category) => $schedules->has($category))
            ->map(fn (string $label, string $category) => [
                'category' => $category,
                'label' => $label,
                'items' => $schedules[$category]->map(fn (ServiceSchedule $schedule) => [
                    'id' => $schedule->id,
                    'name' => $schedule->name,
                    'day' => $schedule->dayName(),
                    'time' => $schedule->time(),
                    'location' => $schedule->location,
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * Public URL of an uploaded segment image.
     */
    public static function imageUrl(?string $path, string $kind = 'segment_image'): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        return Storage::disk(config("modules.landing.{$kind}.disk"))->url($path);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(SiteSection $section): array
    {
        $content = $section->content;

        // The first homepage segment plays Admin's videos, or the live stream
        // while it is on air.
        if ($section->key === 'hero') {
            $content['videos'] = HeroVideo::query()->active()->get()
                ->map(fn (HeroVideo $video) => [
                    'id' => $video->id,
                    'title' => $video->title,
                    'url' => $video->url(),
                    'poster' => $video->posterUrl(),
                    'type' => $video->mime,
                ])
                ->values()
                ->all();
            $content['live'] = LiveStream::present($content['live'] ?? []);
            $content['video_mode'] = ($content['video_mode'] ?? 'frame') === 'background' ? 'background' : 'frame';
        }

        // Ongoing and upcoming events from the Events module.
        if ($section->key === 'events') {
            $content['items'] = $this->events->forHome();
            $content['url'] = route('events.public.index');
        }

        if ($section->key === 'pastor') {
            $content['photo_url'] = self::imageUrl($content['photo'] ?? null, 'pastor_photo');
            unset($content['photo']);
        }

        if (in_array($section->key, SiteSection::SEGMENT_KEYS, true)) {
            $content['image_url'] = self::imageUrl($content['image'] ?? null);
            unset($content['image']);
        }

        // The devotional section shows the newest published devotional from
        // the Devotionals module; its own content is the fallback sample.
        if ($section->key === 'devotional' && ($latest = $this->devotionals->latest()) !== null) {
            $content = [
                'label' => $content['label'] ?? 'Renungan Harian',
                'title' => $latest['title'],
                'reference' => $latest['reference'],
                'verse' => $latest['verse'] ?? '',
                'excerpt' => $latest['excerpt'],
                'url' => $latest['url'],
                'is_sample' => false,
            ];
        }

        // Photos of the album Admin picked (or the newest one) from the Gallery
        // module, in the chosen layout; the sample tiles stay as fallback.
        if ($section->key === 'gallery') {
            $home = $this->gallery->forHome(isset($content['album_id']) ? (int) $content['album_id'] : null);
            $content['layout'] = $content['layout'] ?? 'grid';

            if ($home['photos'] !== []) {
                $content['items'] = array_map(fn (array $photo) => [
                    'caption' => $photo['caption'] ?? '',
                    'image' => $photo['thumbnailUrl'],
                    'full' => $photo['url'],
                ], $home['photos']);
                $content['url'] = $home['album']['url'] ?? null;
                $content['album'] = $home['album']['title'] ?? null;
                $content['is_sample'] = false;
            }

            $content['index_url'] = route('gallery.public.index');
            unset($content['album_id']);
        }

        // "Kesaksian terpilih": approved testimonies from the Testimonies module.
        if ($section->key === 'testimonies') {
            $content['items'] = $this->testimonies->forHome();
            $content['url'] = route('testimonies.public.index');
        }

        return $content;
    }
}
