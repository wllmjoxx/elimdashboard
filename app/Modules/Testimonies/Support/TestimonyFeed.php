<?php

namespace App\Modules\Testimonies\Support;

use App\Modules\Testimonies\Models\Testimony;

/**
 * Approved testimonies in the shape public pages render. The Landing
 * homepage reads them through this class.
 */
class TestimonyFeed
{
    /**
     * Featured testimonies for the homepage carousel, or the newest approved
     * ones when Admin has not featured any.
     *
     * @return list<array<string, mixed>>
     */
    public function forHome(): array
    {
        $featured = Testimony::query()->approved()->where('is_featured', true)->limit(10)->get();

        $items = $featured->isNotEmpty()
            ? $featured
            : Testimony::query()->approved()->limit((int) config('modules.testimonies.home_fallback'))->get();

        return $items->map(fn (Testimony $testimony) => $this->present($testimony))->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function present(Testimony $testimony): array
    {
        return [
            'id' => $testimony->id,
            'title' => $testimony->title,
            'body' => $testimony->body,
            'excerpt' => $testimony->excerpt(),
            'category' => $testimony->category->label(),
            'name' => $testimony->shownName(),
        ];
    }
}
