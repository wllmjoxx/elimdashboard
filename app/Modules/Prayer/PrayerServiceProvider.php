<?php

namespace App\Modules\Prayer;

use App\Modules\Prayer\Database\Seeders\PrayerSeeder;
use App\Modules\Prayer\Models\PrayerRequest;
use App\Modules\Prayer\Policies\PrayerRequestPolicy;
use App\Support\Modules\ModuleServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;

class PrayerServiceProvider extends ModuleServiceProvider
{
    public function key(): string
    {
        return 'prayer';
    }

    public function name(): string
    {
        return 'Doa Mingguan';
    }

    public function navigation(): array
    {
        return [
            ['title' => 'Doa Mingguan', 'route' => 'prayer.index', 'icon' => 'index', 'permission' => 'prayer.view', 'group' => 'Pelayanan', 'order' => 40],
            ['title' => 'Riwayat Doa', 'route' => 'prayer.history', 'icon' => 'history', 'group' => 'Akun', 'order' => 210],
        ];
    }

    public function permissions(): array
    {
        return [
            'prayer.view',
            'prayer.mark',
            'prayer.export',
            'prayer.delete',
        ];
    }

    public function seeders(): array
    {
        return [
            PrayerSeeder::class,
        ];
    }

    protected function bootModule(): void
    {
        Gate::policy(PrayerRequest::class, PrayerRequestPolicy::class);
        $this->loadViewsFrom($this->path('resources/views'), 'prayer');

        // Guests: a few per hour per IP on top of the captcha. Members are
        // also held to the weekly limit in StorePrayerRequest (PRD 4.6).
        RateLimiter::for('prayer-form', fn (Request $request) => $request->user()
            ? Limit::perHour(10)->by('prayer-user:'.$request->user()->id)
            : Limit::perHour((int) config('modules.prayer.guest_hourly_limit'))->by('prayer-ip:'.$request->ip()));
    }
}
