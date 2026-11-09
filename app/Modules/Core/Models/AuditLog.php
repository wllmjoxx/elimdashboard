<?php

namespace App\Modules\Core\Models;

use App\Models\User;
use App\Modules\Core\Support\CurrentChurch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Audit trail (PRD 4.13). Modules call AuditLog::record() whenever sensitive
 * data is read or changed, e.g. every view of prayer requests (PRD 4.6).
 *
 * @property int $id
 * @property int|null $church_id
 * @property int|null $user_id
 * @property string $action
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property array<string, mixed>|null $metadata
 * @property string|null $ip
 * @property Carbon $created_at
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Record an action by the signed-in user (or the system).
     *
     * @param  array<string, mixed>  $metadata
     */
    public static function record(string $action, ?Model $subject = null, array $metadata = []): self
    {
        $user = auth()->user();
        $current = app(CurrentChurch::class);

        return static::query()->create([
            'church_id' => $subject?->getAttribute('church_id') ?? $current->idForNewRecords(),
            'user_id' => $user?->getAuthIdentifier(),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'metadata' => $metadata === [] ? null : $metadata,
            'ip' => request()?->ip(),
        ]);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
