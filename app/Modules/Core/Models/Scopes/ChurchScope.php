<?php

namespace App\Modules\Core\Models\Scopes;

use App\Modules\Core\Support\CurrentChurch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Limits a branch table to the current branch (PRD 4.10). Added by the
 * BelongsToChurch trait; remove it with withoutGlobalScope(ChurchScope::class).
 */
class ChurchScope implements Scope
{
    /**
     * @param  Builder<Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        $current = app(CurrentChurch::class);

        if (! $current->restricts()) {
            return;
        }

        $id = $current->id();

        // A restricted request without any branch must not fall back to
        // showing every branch.
        if ($id === null) {
            $builder->whereRaw('0 = 1');

            return;
        }

        $builder->where($model->qualifyColumn('church_id'), $id);
    }
}
