<?php

namespace App\Modules\Members\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\Church;
use App\Modules\Members\Enums\MemberStatus;
use App\Modules\Members\Http\Requests\RegisterMemberRequest;
use App\Modules\Members\Models\Member;
use App\Modules\Members\Notifications\RegistrationReceived;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Member registration (PRD 4.4): full name, email, optional phone and
 * branch. No password yet; the account waits for Admin approval.
 */
class RegisterController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('members/auth/register', [
            'churches' => Church::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'defaultChurchId' => Church::default()?->id,
        ]);
    }

    public function store(RegisterMemberRequest $request): RedirectResponse
    {
        $member = new Member;
        $member->forceFill([
            ...$request->safe()->only(['name', 'email', 'phone', 'church_id']),
            'status' => MemberStatus::Pending,
            // Unusable until the member sets one from the approval email.
            'password' => Str::random(64),
        ])->save();

        $member->notify(new RegistrationReceived);

        return to_route('login')->with('status', 'Pendaftaran Anda sudah kami terima. Kami akan mengabari lewat email setelah Admin meninjaunya.');
    }
}
