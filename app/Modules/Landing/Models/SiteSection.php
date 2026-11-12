<?php

namespace App\Modules\Landing\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int|null $church_id
 * @property string $key
 * @property array<string, mixed> $content
 * @property bool $is_active
 * @property int $sort_order
 */
class SiteSection extends Model
{
    /**
     * The three home segments editable from the admin panel: a wide
     * background image with a title, text and an optional button.
     */
    public const SEGMENT_KEYS = ['segment_1', 'segment_2', 'segment_3'];

    /**
     * Section keys the site knows how to render. Each key has a matching
     * component in resources/js/modules/landing/sections.
     */
    public const KEYS = [
        'hero',
        'about',
        'mission',
        'devotional',
        'schedule',
        'prayer',
        'gallery',
        'testimonies',
        'contact',
        'privacy',
        'pastor',
        'events',
        ...self::SEGMENT_KEYS,
    ];

    /**
     * Sections shown on the homepage. The other sections have their own page
     * (Tentang, Renungan, Jadwal, Form Doa, Contact,
     * Kebijakan Privasi); devotional and schedule
     * appear on both, as a short teaser on the homepage.
     */
    public const HOME_KEYS = [
        'hero',
        'events',
        'segment_1',
        'devotional',
        'schedule',
        'segment_2',
        'gallery',
        'segment_3',
        'pastor',
        'testimonies',
    ];

    protected $fillable = [
        'church_id',
        'key',
        'content',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Active, renderable sections in page order.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function visible(Builder $query): void
    {
        $query->where('is_active', true)
            ->whereIn('key', self::KEYS)
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
