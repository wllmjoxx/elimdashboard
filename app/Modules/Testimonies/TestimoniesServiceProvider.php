<?php

namespace App\Modules\Testimonies;

use App\Modules\Testimonies\Database\Seeders\TestimoniesSeeder;
use App\Modules\Testimonies\Models\Testimony;
use App\Modules\Testimonies\Policies\TestimonyPolicy;
use App\Support\Modules\ModuleServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;

class TestimoniesServiceProvider extends ModuleServiceProvider
{
    public function key(): string
    {
        return 'testimonies';
    }

    public function name(): string
    {
        return 'Kesaksian';
    }

    public function navigation(): array
    {
        return [
            ['title' => 'Kesaksian', 'route' => 'testimonies.index', 'icon' => 'index', 'permission' => 'testimonies.moderate', 'group' => 'Pelayanan', 'order' => 50],
        ];
    }

    public function permissions(): array
    {
        return [
            'testimonies.view',
            'testimonies.moderate',
        ];
    }

    public function seeders(): array
    {
        return [
            TestimoniesSeeder::class,
        ];
    }

    protected function bootModule(): void
    {
        Gate::policy(Testimony::class, TestimonyPolicy::class);

        // The limiter stores only a hash of the key in the cache, and it
        // expires with the window: a short-lived IP hash (PRD 4.7). The IP is
        // hashed here as well so even the cache key holds no plain address.
        RateLimiter::for('testimony-form', fn (Request $request) => Limit::perHour((int) config('modules.testimonies.hourly_limit'))
            ->by('testimony:'.hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'))));
    }
}
