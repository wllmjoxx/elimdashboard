<?php

namespace App\Modules\Gallery\Models;

use App\Modules\Core\Models\Concerns\BelongsToChurch;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A photo album (PRD 4.8). Scoped to the current branch.
 *
 * @property int $id
 * @property int $church_id
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property Carbon|null $event_date
 * @property bool $is_active
 * @property int $sort_order
 */
class Album extends Model
{
    use BelongsToChurch;

    protected $fillable = [
        'title',
        'description',
        'event_date',
        'is_active',
    ];

    protected $attributes = [
        'is_active' => true,
        'sort_order' => 0,
    ];

    protected static function booted(): void
    {
        static::creating(function (Album $album) {
            $album->slug ??= static::uniqueSlug($album->title, $album->church_id);
        });
    }

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Active albums in display order: newest event first.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function visible(Builder $query): void
    {
        $query->where('is_active', true)
            ->orderByDesc('event_date')
            ->orderByDesc('id');
    }

    /**
     * @return HasMany<AlbumPhoto, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(AlbumPhoto::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasMany<AlbumPhoto, $this>
     */
    public function activePhotos(): HasMany
    {
        return $this->photos()->where('is_active', true);
    }

    /**
     * @return HasOne<AlbumPhoto, $this>
     */
    public function cover(): HasOne
    {
        return $this->hasOne(AlbumPhoto::class)->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * URL slug unique within the branch. Set on create; call it directly
     * where model events are off (DatabaseSeeder).
     */
    public static function uniqueSlug(string $title, ?int $churchId): string
    {
        $base = Str::slug($title) ?: 'album';
        $slug = $base;
        $suffix = 2;

        while (static::query()->withoutGlobalScopes()->where('church_id', $churchId)->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
