<?php

namespace App\Modules\Members\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Members\Http\Requests\UpdateProfileRequest;
use App\Modules\Members\Models\Member;
use App\Modules\Members\Support\ProfilePresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The signed-in member's own profile (PRD 4.5).
 */
class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $member = $this->member($request);

        return Inertia::render('members/profile', [
            'profile' => ProfilePresenter::present($member),
            'options' => ProfilePresenter::options(),
            'photoRules' => ProfilePresenter::photoRules(),
            'flash' => $request->session()->get('status'),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $member = $this->member($request);
        $profile = $member->profileOrNew();
        $config = config('modules.members.photo');
        $disk = Storage::disk($config['disk']);
        $oldPhoto = $profile->photo;

        if ($request->hasFile('photo')) {
            $profile->photo = $request->file('photo')->store($config['directory'], $config['disk']);
        } elseif ($request->boolean('remove_photo')) {
            $profile->photo = null;
        }

        $profile->fill([
            'nickname' => $request->validated('nickname'),
            'birth_date' => $request->validated('birth_date'),
            'gender' => $request->validated('gender'),
            'address' => $request->validated('address'),
            'privacy_settings' => [
                'show_birthday' => $request->boolean('show_birthday'),
                'show_phone' => $request->boolean('show_phone'),
            ],
        ]);

        DB::transaction(function () use ($member, $profile, $request) {
            $member->forceFill($request->safe()->only(['name', 'phone']))->save();
            $profile->save();
        });

        if ($oldPhoto && $oldPhoto !== $profile->photo) {
            $disk->delete($oldPhoto);
        }

        return back()->with('status', 'Profil tersimpan.');
    }

    /**
     * Ask Admin to delete the account (PRD 4.5). Admin decides; until then
     * the account works as usual.
     */
    public function requestDeletion(Request $request): RedirectResponse
    {
        $profile = $this->member($request)->profileOrNew();
        $profile->deletion_requested_at = now();
        $profile->save();

        return back()->with('status', 'Permintaan penghapusan akun sudah dikirim ke Admin.');
    }

    public function cancelDeletion(Request $request): RedirectResponse
    {
        $this->member($request)->profile?->update(['deletion_requested_at' => null]);

        return back()->with('status', 'Permintaan penghapusan akun dibatalkan.');
    }

    private function member(Request $request): Member
    {
        return Member::query()->with(['profile', 'church:id,name'])->findOrFail($request->user()->id);
    }
}
