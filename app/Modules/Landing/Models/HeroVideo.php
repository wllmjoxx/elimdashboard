<?php

namespace App\Modules\Landing\Models;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int|null $church_id
 * @property string|null $title
 * @property string $path
 * @property string|null $poster_path
 * @property string $mime
 * @property int $size
 * @property bool $is_active
 * @property int $sort_order
 */
class HeroVideo extends Model
{
    protected $fillable = [
        'church_id',
        'title',
        'path',
        'poster_path',
        'mime',
        'size',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'size' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    public function url(): string
    {
        return self::disk()->url($this->path);
    }

    public function posterUrl(): ?string
    {
        return $this->poster_path ? self::disk()->url($this->poster_path) : null;
    }

    /**
     * Removes the files along with the record.
     */
    public function deleteWithFiles(): void
    {
        self::disk()->delete(array_filter([$this->path, $this->poster_path]));
        $this->delete();
    }

    public static function disk(): Filesystem
    {
        return Storage::disk(config('modules.landing.hero_video.disk'));
    }
}
