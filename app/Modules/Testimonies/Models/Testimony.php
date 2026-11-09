<?php

namespace App\Modules\Testimonies\Models;

use App\Models\User;
use App\Modules\Core\Models\Concerns\BelongsToChurch;
use App\Modules\Testimonies\Enums\TestimonyCategory;
use App\Modules\Testimonies\Enums\TestimonyStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * An anonymous testimony (PRD 4.7). Holds no sender identity at all.
 *
 * @property int $id
 * @property int $church_id
 * @property string $title
 * @property string $body
 * @property TestimonyCategory $category
 * @property string|null $display_name
 * @property TestimonyStatus $status
 * @property bool $is_featured
 * @property int|null $moderated_by
 * @property Carbon|null $moderated_at
 * @property string|null $moderation_note
 * @property Carbon $created_at
 */
class Testimony extends Model
{
    use BelongsToChurch;

    protected $fillable = [
        'title',
        'body',
        'category',
        'display_name',
    ];

    protected $attributes = [
        'status' => 'pending',
        'is_featured' => false,
    ];

    protected function casts(): array
    {
        return [
            'category' => TestimonyCategory::class,
            'status' => TestimonyStatus::class,
            'is_featured' => 'boolean',
            'moderated_at' => 'datetime',
        ];
    }

    /**
     * Approved testimonies, newest approval first.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function approved(Builder $query): void
    {
        $query->where('status', TestimonyStatus::Approved->value)
            ->orderByDesc('moderated_at')
            ->orderByDesc('id');
    }

    public function shownName(): string
    {
        return filled($this->display_name) ? (string) $this->display_name : 'Anonim';
    }

    public function excerpt(int $limit = 280): string
    {
        return Str::limit(trim(preg_replace('/\s+/', ' ', $this->body) ?? ''), $limit);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }
}
