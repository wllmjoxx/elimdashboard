<?php

namespace App\Modules\Landing\Support;

use App\Modules\Landing\Models\ServiceSchedule;
use App\Modules\Landing\Models\SiteSection;
use Illuminate\Support\Facades\Storage;

/**
 * Turns landing data into the props the public pages render.
 */
class LandingContent
{
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
    public static function imageUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        return Storage::disk(config('modules.landing.segment_image.disk'))->url($path);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(SiteSection $section): array
    {
        $content = $section->content;

        if (in_array($section->key, SiteSection::SEGMENT_KEYS, true)) {
            $content['image_url'] = self::imageUrl($content['image'] ?? null);
            unset($content['image']);
        }

        return $content;
    }
}
