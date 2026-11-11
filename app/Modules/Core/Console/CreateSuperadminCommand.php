<?php

namespace App\Modules\Core\Console;

use App\Models\User;
use App\Modules\Core\Actions\SyncRolesAndPermissions;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\Church;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

/**
 * The first account on a fresh server (staging, production), where the
 * dummy accounts of the seeders are not created. Asks for the password so
 * it never appears in shell history.
 */
#[Signature('core:create-superadmin {email : Email address} {--name= : Display name}')]
#[Description('Create (or promote) a Superadmin account with a password you type in')]
class CreateSuperadminCommand extends Command
{
    public function handle(SyncRolesAndPermissions $sync): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $name = (string) ($this->option('name') ?: $this->ask('Nama', 'Superadmin'));
        $password = (string) $this->secret('Password (minimal 12 karakter)');
        $confirmation = (string) $this->secret('Ulangi password');

        $validator = Validator::make(
            ['email' => $email, 'name' => $name, 'password' => $password, 'password_confirmation' => $confirmation],
            [
                'email' => ['required', 'email', 'max:255'],
                'name' => ['required', 'string', 'max:255'],
                'password' => ['required', 'string', 'min:12', 'confirmed'],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $sync->handle();

        $user = User::query()->firstOrNew(['email' => $email]);
        $user->forceFill([
            'name' => $name,
            'password' => $password,
            'church_id' => $user->church_id ?? Church::default()?->id,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();
        $user->assignRole(Role::Superadmin->value);

        $this->info("Superadmin {$email} siap. Masuk di /login.");

        return self::SUCCESS;
    }
}
