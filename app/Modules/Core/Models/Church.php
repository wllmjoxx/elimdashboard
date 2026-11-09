<?php

namespace App\Modules\Core\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A branch of GGP (PRD 4.10). Core tables point here through church_id and
 * are scoped to the current branch with the BelongsToChurch trait.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $address
 * @property array{phone?: string, whatsapp?: string, email?: string}|null $contact
 * @property string|null $logo
 * @property string $timezone
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'slug', 'address', 'contact', 'logo', 'timezone', 'is_active'])]
class Church extends Model
{
    protected function casts(): array
    {
        return [
            'contact' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The branch for visitors and users without one (config modules.core.default_church).
     */
    public static function default(): ?self
    {
        return static::query()->where('slug', config('modules.core.default_church'))->first();
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
