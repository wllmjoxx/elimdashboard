<?php

namespace App\Modules\Members;

use App\Models\User;
use App\Modules\Members\Database\Seeders\MembersSeeder;
use App\Modules\Members\Enums\MemberStatus;
use App\Modules\Members\Http\Middleware\EnsureAccountIsActive;
use App\Modules\Members\Models\Member;
use App\Modules\Members\Policies\MemberPolicy;
use App\Support\Modules\ModuleServiceProvider;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

class MembersServiceProvider extends ModuleServiceProvider
{
    public function key(): string
    {
        return 'members';
    }

    public function name(): string
    {
        return 'Jemaat';
    }

    public function navigation(): array
    {
        return [
            ['title' => 'Jemaat', 'route' => 'members.index', 'icon' => 'index', 'permission' => 'members.view', 'group' => 'Administrasi', 'order' => 60],
        ];
    }

    public function permissions(): array
    {
        return [
            'members.view',
            'members.approve',
            'members.deactivate',
        ];
    }

    public function seeders(): array
    {
        return [
            MembersSeeder::class,
        ];
    }

    protected function bootModule(): void
    {
        Gate::policy(Member::class, MemberPolicy::class);

        $this->signInOnlyActiveAccounts();

        $active = fn (HttpKernel $kernel) => $kernel->appendMiddlewareToGroup('web', EnsureAccountIsActive::class);

        if ($this->app->resolved(HttpKernelContract::class)) {
            $active($this->app->make(HttpKernelContract::class));
        } else {
            $this->app->afterResolving(HttpKernelContract::class, $active);
        }
    }

    /**
     * Password sign-in tells pending, rejected and inactive accounts why
     * they cannot enter (PRD 4.4).
     */
    private function signInOnlyActiveAccounts(): void
    {
        Fortify::authenticateUsing(function (Request $request) {
            $user = User::query()->where(Fortify::username(), $request->input(Fortify::username()))->first();

            if (! $user || ! Hash::check((string) $request->input('password'), $user->password)) {
                return null;
            }

            $status = MemberStatus::tryFrom((string) $user->getAttribute('status')) ?? MemberStatus::Active;

            if ($status !== MemberStatus::Active) {
                throw ValidationException::withMessages([Fortify::username() => $status->loginMessage()]);
            }

            return $user;
        });
    }
}
