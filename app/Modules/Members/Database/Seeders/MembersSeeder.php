<?php

namespace App\Modules\Members\Database\Seeders;

use App\Modules\Core\Models\Church;
use App\Modules\Members\Enums\MemberStatus;
use App\Modules\Members\Models\Member;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MembersSeeder extends Seeder
{
    /**
     * Dummy registrations waiting for approval, so the Jemaat panel has
     * something to review. Runs after CoreSeeder; safe to run again.
     */
    public function run(): void
    {
        $church = Church::default();

        $pending = [
            ['Maria Siahaan', 'maria.daftar@example.com', '0812 1111 2222'],
            ['Yohanes Pratama', 'yohanes.daftar@example.com', null],
            ['Debora Lumban', 'debora.daftar@example.com', '0857 3333 4444'],
        ];

        foreach ($pending as [$name, $email, $phone]) {
            if (Member::query()->where('email', $email)->exists()) {
                continue;
            }

            (new Member)->forceFill([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'church_id' => $church?->id,
                'status' => MemberStatus::Pending,
                'password' => Str::random(64),
            ])->save();
        }
    }
}
