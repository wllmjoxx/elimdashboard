<?php

namespace App\Modules\Core\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $email
 * @property string $code_hash
 * @property Carbon $expires_at
 * @property int $attempts
 * @property Carbon|null $used_at
 * @property Carbon $created_at
 */
class LoginOtp extends Model
{
    use MassPrunable;

    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $hidden = ['code_hash'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    /**
     * Codes live minutes; a day later they are only clutter (pruned daily).
     *
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subDay());
    }

    public function isUsable(): bool
    {
        return $this->used_at === null
            && $this->expires_at->isFuture()
            && $this->attempts < (int) config('modules.core.otp.max_attempts');
    }
}
