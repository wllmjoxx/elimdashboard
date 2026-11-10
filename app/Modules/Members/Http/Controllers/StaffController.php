<?php

namespace App\Modules\Members\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\AuditLog;
use App\Modules\Core\Models\Church;
use App\Modules\Core\Support\CurrentChurch;
use App\Modules\Members\Enums\MemberStatus;
use App\Modules\Members\Models\Member;
use App\Modules\Members\Notifications\StaffInvitation;
use App\Modules\Members\Support\RoleAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Staff accounts (PRD 4.2, 4.4): change an account's role and invite new
 * staff by email. Every change goes to the audit log.
 */
class StaffController extends Controller
{
    public function __construct(private readonly RoleAssignment $roles) {}

    public function updateRole(Request $request, Member $member): RedirectResponse
    {
        Gate::authorize('assignRole', $member);
        // Gate::before lets Superadmin pass every check; still never let anyone
        // change their own role and lock themselves out.
        abort_if($request->user()->is($member), 403);

        $role = Role::from($request->validate([
            'role' => ['required', Rule::in($this->allowed($request->user()))],
        ], [], ['role' => 'role'])['role']);

        $previous = $this->roles->current($member);
        $member->syncRoles([$role->value]);

        AuditLog::record('members.role.changed', $member, [
            'from' => $previous?->value,
            'to' => $role->value,
        ]);

        return back()->with('status', "Role {$member->name} sekarang {$role->label()}.");
    }

    public function invite(Request $request, CurrentChurch $current): RedirectResponse
    {
        Gate::authorize('invite', Member::class);

        $actor = $request->user();
        $restricted = $current->restricts();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'role' => ['required', Rule::in($this->allowed($actor))],
            'church_id' => $restricted
                ? ['prohibited']
                : ['required', 'integer', Rule::exists('churches', 'id')->where('is_active', true)],
        ], [
            'email.unique' => 'Email ini sudah terdaftar. Ubah role akunnya dari halaman profil jemaat.',
        ], ['name' => 'nama', 'role' => 'role', 'church_id' => 'cabang']);

        $role = Role::from($validated['role']);

        $member = DB::transaction(function () use ($validated, $actor, $restricted, $current, $role) {
            $member = new Member;
            $member->forceFill([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'church_id' => $restricted ? $current->id() : $validated['church_id'],
                'status' => MemberStatus::Active,
                'approved_by' => $actor->id,
                'approved_at' => now(),
                // Unusable until they set one from the invitation email.
                'password' => Str::random(64),
            ])->save();

            $member->syncRoles([$role->value]);

            return $member;
        });

        $member->notify(new StaffInvitation($role->label(), $actor->name));

        AuditLog::record('members.invited', $member, ['role' => $role->value]);

        return back()->with('status', "Undangan dikirim ke {$member->email} sebagai {$role->label()}.");
    }

    /**
     * @return list<string>
     */
    private function allowed(User $actor): array
    {
        return array_map(fn (Role $role) => $role->value, $this->roles->assignable($actor));
    }

    /**
     * Active branches Superadmin can invite into.
     *
     * @return list<array{id: int, name: string}>
     */
    public static function churchOptions(CurrentChurch $current): array
    {
        if ($current->restricts()) {
            return [];
        }

        return Church::query()->where('is_active', true)->orderBy('name')->get(['id', 'name'])
            ->map(fn (Church $church) => ['id' => $church->id, 'name' => $church->name])
            ->values()
            ->all();
    }
}
