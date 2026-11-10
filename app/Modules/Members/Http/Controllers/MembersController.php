<?php

namespace App\Modules\Members\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Support\CurrentChurch;
use App\Modules\Members\Enums\MemberStatus;
use App\Modules\Members\Models\Member;
use App\Modules\Members\Notifications\RegistrationApproved;
use App\Modules\Members\Notifications\RegistrationNeedsInfo;
use App\Modules\Members\Notifications\RegistrationRejected;
use App\Modules\Members\Support\RoleAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin panel for accounts (PRD 4.4, 4.8): approve, reject or ask for more
 * data on registrations, and deactivate or reactivate accounts. Admin sees
 * only their branch.
 */
class MembersController extends Controller
{
    public function index(Request $request, CurrentChurch $current): Response
    {
        Gate::authorize('viewAny', Member::class);

        $status = MemberStatus::tryFrom((string) $request->query('status')) ?? MemberStatus::Pending;

        $counts = Member::query()->inCurrentChurch()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $members = Member::query()->inCurrentChurch()
            ->withStatus($status)
            ->with(['church:id,name', 'roles:id,name', 'profile'])
            ->orderBy($status === MemberStatus::Pending ? 'created_at' : 'name')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Member $member) => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'phone' => $member->phone,
                'church' => $member->church?->name,
                'roles' => $member->roles->map(fn ($role) => Role::tryFrom($role->name)?->label() ?? $role->name)->values(),
                'reviewNote' => $member->review_note,
                'deletionRequested' => $member->profile?->deletion_requested_at !== null,
                'registeredAt' => $member->created_at?->toIso8601String(),
                'approvedAt' => $member->approved_at?->toIso8601String(),
                'can' => [
                    'review' => $request->user()->can('review', $member),
                    'deactivate' => $request->user()->can('deactivate', $member),
                ],
            ]);

        return Inertia::render('members/index', [
            'status' => $status->value,
            'statuses' => array_map(fn (MemberStatus $item) => [
                'value' => $item->value,
                'label' => $item->label(),
                'count' => (int) ($counts[$item->value] ?? 0),
            ], MemberStatus::cases()),
            'members' => $members,
            'showChurch' => ! $current->restricts(),
            'flash' => $request->session()->get('status'),
            'invite' => $request->user()->can('invite', Member::class) ? [
                'roles' => app(RoleAssignment::class)->options($request->user()),
                'churches' => StaffController::churchOptions($current),
            ] : null,
        ]);
    }

    public function approve(Request $request, Member $member): RedirectResponse
    {
        Gate::authorize('review', $member);
        abort_unless($member->status === MemberStatus::Pending, 409);

        $member->forceFill([
            'status' => MemberStatus::Active,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'review_note' => null,
        ])->save();
        $member->syncRoles([Role::Member->value]);

        $member->notify(new RegistrationApproved);

        return back()->with('status', "{$member->name} disetujui. Email untuk membuat password sudah dikirim.");
    }

    public function reject(Request $request, Member $member): RedirectResponse
    {
        Gate::authorize('review', $member);
        abort_unless($member->status === MemberStatus::Pending, 409);

        $reason = $request->validate(['note' => ['required', 'string', 'max:1000']], [], ['note' => 'alasan'])['note'];

        $member->forceFill(['status' => MemberStatus::Rejected, 'review_note' => $reason])->save();
        $member->notify(new RegistrationRejected($reason));

        return back()->with('status', "Pendaftaran {$member->name} ditolak.");
    }

    public function requestInfo(Request $request, Member $member): RedirectResponse
    {
        Gate::authorize('review', $member);
        abort_unless($member->status === MemberStatus::Pending, 409);

        $note = $request->validate(['note' => ['required', 'string', 'max:1000']], [], ['note' => 'pesan'])['note'];

        $member->forceFill(['review_note' => $note])->save();
        $member->notify(new RegistrationNeedsInfo($note));

        return back()->with('status', "Permintaan data dikirim ke {$member->name}.");
    }

    public function updateStatus(Request $request, Member $member): RedirectResponse
    {
        Gate::authorize('deactivate', $member);

        $status = MemberStatus::from($request->validate([
            'status' => ['required', Rule::in([MemberStatus::Active->value, MemberStatus::Inactive->value])],
        ])['status']);

        abort_unless(in_array($member->status, [MemberStatus::Active, MemberStatus::Inactive], true), 409);

        $member->forceFill(['status' => $status])->save();

        return back()->with('status', $status === MemberStatus::Inactive
            ? "Akun {$member->name} dinonaktifkan."
            : "Akun {$member->name} diaktifkan kembali.");
    }
}
