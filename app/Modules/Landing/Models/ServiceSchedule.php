<?php

namespace App\Modules\Landing\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int|null $church_id
 * @property string $category
 * @property string $name
 * @property int $day
 * @property string $start_time
 * @property string|null $location
 * @property int $sort_order
 * @property bool $is_active
 */
class ServiceSchedule extends Model
{
    /**
     * Schedule groups in display order.
     */
    public const CATEGORIES = [
        'raya' => 'Ibadah Raya',
        'komisi' => 'Ibadah Komisi',
        'doa' => 'Menara Doa',
    ];

    public const DAYS = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    protected $fillable = [
        'church_id',
        'category',
        'name',
        'day',
        'start_time',
        'location',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'day' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function visible(Builder $query): void
    {
        $query->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function dayName(): string
    {
        return self::DAYS[$this->day] ?? '';
    }

    /**
     * "08.00" (Indonesian time notation).
     */
    public function time(): string
    {
        return str_replace(':', '.', substr($this->start_time, 0, 5));
    }
}
