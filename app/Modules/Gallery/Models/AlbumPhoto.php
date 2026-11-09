<?php

namespace App\Modules\Gallery\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * A photo in an album. Files live on the public disk (the gallery is
 * public); branch scoping comes through the album.
 *
 * @property int $id
 * @property int $album_id
 * @property string $image
 * @property string $thumbnail
 * @property int|null $width
 * @property int|null $height
 * @property string|null $caption
 * @property int $sort_order
 * @property bool $is_active
 * @property int|null $uploaded_by
 */
class AlbumPhoto extends Model
{
    protected $fillable = [
        'caption',
        'is_active',
    ];

    protected $attributes = [
        'is_active' => true,
        'sort_order' => 0,
    ];

    protected static function booted(): void
    {
        // Remove the files with the row.
        static::deleted(function (AlbumPhoto $photo) {
            Storage::disk(config('modules.gallery.disk'))->delete(array_unique([$photo->image, $photo->thumbnail]));
        });
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    public function url(): string
    {
        return Storage::disk(config('modules.gallery.disk'))->url($this->image);
    }

    public function thumbnailUrl(): string
    {
        return Storage::disk(config('modules.gallery.disk'))->url($this->thumbnail);
    }

    /**
     * @return BelongsTo<Album, $this>
     */
    public function album(): BelongsTo
    {
        return $this->belongsTo(Album::class);
    }
}
