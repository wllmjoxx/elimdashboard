<?php

namespace App\Modules\Members\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Members\Enums\MembershipStatus;
use App\Modules\Members\Models\Member;
use App\Modules\Members\Support\ProfilePresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin view of one member's profile, membership data kept by Admin, and
 * the private profile photo.
 */
class MemberProfileController extends Controller
{
    public function show(Request $request, Member $member): Response
    {
        Gate::authorize('view', $member);
        $member->load(['profile', 'church:id,name']);

        return Inertia::render('members/show', [
            'profile' => ProfilePresenter::present($member),
            'options' => ProfilePresenter::options(),
            'can' => [
                'updateMembership' => $request->user()->can('updateMembership', $member),
            ],
            'flash' => $request->session()->get('status'),
        ]);
    }

    public function updateMembership(Request $request, Member $member): RedirectResponse
    {
        Gate::authorize('updateMembership', $member);

        $validated = $request->validate([
            'membership_status' => ['required', Rule::enum(MembershipStatus::class)],
            'joined_at' => ['nullable', 'date', 'before_or_equal:today'],
            'baptized' => ['boolean'],
        ], [], [
            'membership_status' => 'status keanggotaan',
            'joined_at' => 'tanggal bergabung',
        ]);

        $profile = $member->profileOrNew();
        $profile->fill([...$validated, 'baptized' => $request->boolean('baptized')])->save();

        return back()->with('status', 'Data keanggotaan tersimpan.');
    }

    public function photo(Member $member): StreamedResponse
    {
        Gate::authorize('view', $member);

        $path = $member->profile?->photo;
        $disk = Storage::disk(config('modules.members.photo.disk'));

        abort_unless($path && $disk->exists($path), 404);

        return $disk->response($path, headers: ['Cache-Control' => 'private, max-age=86400']);
    }
}
