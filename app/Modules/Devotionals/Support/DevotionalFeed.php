<?php

namespace App\Modules\Devotionals\Support;

use App\Modules\Devotionals\Models\Devotional;

/**
 * Published devotionals in the shape public pages render. Other modules
 * (the Landing homepage) read devotionals through this class.
 */
class DevotionalFeed
{
    /**
     * The newest devotional visitors may see, or null when none is live.
     *
     * @return array<string, mixed>|null
     */
    public function latest(): ?array
    {
        $devotional = Devotional::query()->live()->first();

        return $devotional ? $this->present($devotional) : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function present(Devotional $devotional, bool $withBody = false): array
    {
        return [
            'id' => $devotional->id,
            'title' => $devotional->title,
            'slug' => $devotional->slug,
            'reference' => $devotional->scripture_ref,
            'verse' => $devotional->scripture_text,
            'excerpt' => $devotional->excerpt(),
            'date' => $devotional->publish_date->toDateString(),
            'dateLabel' => $devotional->publish_date->locale('id')->translatedFormat('l, j F Y'),
            'url' => route('devotionals.public.show', $devotional->slug),
            ...($withBody ? ['body' => $devotional->body] : []),
        ];
    }
}
