<?php

namespace App\Modules\Core\Models\Concerns;

use App\Modules\Core\Models\Church;
use App\Modules\Core\Models\Scopes\ChurchScope;
use App\Modules\Core\Support\CurrentChurch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * For models whose table has a church_id column (PRD 4.10): queries are scoped
 * to the current branch and new records get that branch when none is set.
 *
 * Do not use it on User: the scope reads the logged-in user, so scoping the
 * users table itself would recurse. Filter users with whereBelongsTo() instead.
 *
 * @mixin Model
 */
trait BelongsToChurch
{
    public static function bootBelongsToChurch(): void
    {
        static::addGlobalScope(new ChurchScope);

        static::creating(function (Model $model) {
            if ($model->getAttribute('church_id') === null) {
                $model->setAttribute('church_id', app(CurrentChurch::class)->idForNewRecords());
            }
        });
    }

    /**
     * @return BelongsTo<Church, $this>
     */
    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }
}
