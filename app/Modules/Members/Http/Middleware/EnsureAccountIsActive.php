<?php

namespace App\Modules\Members\Http\Middleware;

use App\Modules\Members\Enums\MemberStatus;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs out accounts that are not active, whatever way they signed in
 * (password, passkey or an old session), so deactivation takes effect at
 * once (PRD 4.4). Added to the "web" group by MembersServiceProvider.
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $status = $user ? MemberStatus::tryFrom((string) $user->getAttribute('status')) : null;

        if ($status !== null && $status !== MemberStatus::Active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return to_route('login')->withErrors(['email' => $status->loginMessage()]);
        }

        return $next($request);
    }
}
