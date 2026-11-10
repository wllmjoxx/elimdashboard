<?php

namespace App\Modules\Core;

use App\Models\User;
use App\Modules\Core\Console\SyncPermissionsCommand;
use App\Modules\Core\Database\Seeders\CoreSeeder;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Http\Middleware\ScopeToCurrentChurch;
use App\Modules\Core\Models\Church;
use App\Modules\Core\Support\CurrentChurch;
use App\Support\Modules\ModuleServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class CoreServiceProvider extends ModuleServiceProvider
{
    public function key(): string
    {
        return 'core';
    }

    public function name(): string
    {
        return 'Cabang & Akses';
    }

    public function navigation(): array
    {
        return [
            ['title' => 'Cabang & Akses', 'route' => 'core.index', 'icon' => 'index', 'permission' => 'core.view', 'group' => 'Administrasi', 'order' => 90],
            ['title' => 'Audit Log', 'route' => 'core.audit', 'icon' => 'audit', 'permission' => 'core.audit.view', 'group' => 'Administrasi', 'order' => 95],
        ];
    }

    public function permissions(): array
    {
        return [
            'core.view',
            'core.churches.manage',
            'core.audit.view',
        ];
    }

    public function seeders(): array
    {
        return [
            CoreSeeder::class,
        ];
    }

    protected function registerModule(): void
    {
        $this->app->singleton(CurrentChurch::class);

        // User lives outside the module, so its branch relation is added here.
        User::resolveRelationUsing('church', fn (User $user) => $user->belongsTo(Church::class, 'church_id', 'id', 'church'));

        if ($this->app->runningInConsole()) {
            $this->commands([SyncPermissionsCommand::class]);
        }
    }

    protected function bootModule(): void
    {
        // The HTTP kernel rewrites the router's groups when it is resolved, so
        // the middleware goes on the kernel's "web" group.
        // It must run before route model binding, or a URL could load a
        // record of another branch before the scope is switched on.
        $scope = fn (HttpKernel $kernel) => $kernel
            ->appendMiddlewareToGroup('web', ScopeToCurrentChurch::class)
            ->addToMiddlewarePriorityBefore(SubstituteBindings::class, ScopeToCurrentChurch::class);

        if ($this->app->resolved(HttpKernelContract::class)) {
            $scope($this->app->make(HttpKernelContract::class));
        } else {
            $this->app->afterResolving(HttpKernelContract::class, $scope);
        }

        $this->configureOtpLimits();

        // Superadmin works across branches and passes every check (PRD 4.2).
        Gate::before(fn ($user) => $user instanceof User && $user->hasRole(Role::Superadmin->value) ? true : null);
    }

    /**
     * Rate limits for email sign-in codes (PRD 4.4, 4.13): per email and per
     * IP for sending, per IP for guessing.
     */
    private function configureOtpLimits(): void
    {
        $config = config('modules.core.otp');

        RateLimiter::for('otp-send', fn (Request $request) => [
            Limit::perMinutes(10, (int) $config['per_email_per_10_minutes'])->by('otp-email:'.Str::lower((string) $request->input('email'))),
            Limit::perHour((int) $config['per_ip_per_hour'])->by('otp-ip:'.$request->ip()),
        ]);

        RateLimiter::for('otp-verify', fn (Request $request) => Limit::perMinute((int) $config['verify_per_minute'])->by('otp-verify:'.$request->ip()));
    }
}
