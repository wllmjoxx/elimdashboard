<?php

namespace App\Modules\Core\Database\Seeders;

use App\Models\User;
use App\Modules\Core\Actions\SyncRolesAndPermissions;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\Church;
use Illuminate\Database\Seeder;

class CoreSeeder extends Seeder
{
    /**
     * Dummy accounts, one per role (password "password"). The shared
     * test@example.com account becomes Superadmin.
     *
     * @var array<string, array{0: string, 1: Role}>
     */
    private const ACCOUNTS = [
        'test@example.com' => ['Test User', Role::Superadmin],
        'admin@example.com' => ['Admin Elim', Role::Admin],
        'doa@example.com' => ['Tim Doa Elim', Role::PrayerTeam],
        'pelayan@example.com' => ['Pelayan Elim', Role::Servant],
        'jemaat@example.com' => ['Jemaat Elim', Role::Member],
    ];

    /**
     * The GGP Elim branch, roles and permissions, and dummy staff accounts.
     * Run automatically by DatabaseSeeder; safe to run again.
     */
    public function run(SyncRolesAndPermissions $sync): void
    {
        $church = Church::query()->updateOrCreate(
            ['slug' => config('modules.core.default_church')],
            [
                'name' => 'GGP Jemaat Elim Ciputat',
                'address' => 'Gg. Buntu (Citanduy), Cipayung, Ciputat, Tangerang Selatan 15418',
                'contact' => ['whatsapp' => '0858 8385 8048'],
                'timezone' => 'Asia/Jakarta',
                'is_active' => true,
            ],
        );

        $sync->handle();

        // Dummy accounts share one known password, so they exist only on
        // developer machines and in tests. On staging and production the
        // first account comes from `php artisan core:create-superadmin`.
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        foreach (self::ACCOUNTS as $email => [$name, $role]) {
            $user = User::query()->firstOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => 'password'],
            );

            $user->forceFill([
                'church_id' => $church->id,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();

            $user->syncRoles([$role->value]);
        }
    }
}
