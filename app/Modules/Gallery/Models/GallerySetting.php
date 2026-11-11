<?php

namespace App\Modules\Gallery\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $church_id
 * @property string $source
 * @property string|null $instagram_token
 * @property string|null $instagram_user_id
 * @property string|null $instagram_username
 * @property Carbon|null $instagram_expires_at
 */
class GallerySetting extends Model
{
    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_INSTAGRAM = 'instagram';

    protected $fillable = [
        'church_id',
        'source',
        'instagram_token',
        'instagram_user_id',
        'instagram_username',
        'instagram_expires_at',
    ];

    protected $hidden = ['instagram_token'];

    protected function casts(): array
    {
        return [
            'instagram_token' => 'encrypted',
            'instagram_expires_at' => 'datetime',
        ];
    }

    /**
     * The one settings row (created on first use).
     */
    public static function current(): self
    {
        return self::query()->firstOrCreate(['church_id' => null], ['source' => self::SOURCE_MANUAL]);
    }

    public function usesInstagram(): bool
    {
        return $this->source === self::SOURCE_INSTAGRAM && filled($this->instagram_token);
    }
}
