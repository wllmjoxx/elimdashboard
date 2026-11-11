<?php

namespace App\Modules\Core\Http\Middleware;

use App\Models\User;
use App\Modules\Core\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shorter sessions for staff accounts (PRD 4.13): anyone with a role other
 * than Jemaat is signed out after modules.core.staff_idle_minutes without a
 * request. "Ingat saya" does not extend it: when the session has expired and
 * the remember cookie signs a staff member back in, they must sign in again.
 */
class LogOutIdleStaff
{
    public const SESSION_KEY = 'core.staff_last_seen';

    public function handle(Request $request, Closure $next): Response
    {
        $minutes = (int) config('modules.core.staff_idle_minutes');
        $user = $request->user();

        if ($minutes <= 0 || ! $user instanceof User || ! $request->hasSession() || ! $this->isStaff($user)) {
            return $next($request);
        }

        $session = $request->session();
        $lastSeen = (int) $session->get(self::SESSION_KEY, 0);
        $idle = $lastSeen > 0 && now()->getTimestamp() - $lastSeen > $minutes * 60;
        $restoredByCookie = $lastSeen === 0 && Auth::guard('web')->viaRemember();

        if ($idle || $restoredByCookie) {
            Auth::guard('web')->logout();
            $session->invalidate();
            $session->regenerateToken();

            return redirect()->guest(route('login'))
                ->with('status', "Sesi berakhir karena tidak ada aktivitas selama {$minutes} menit. Silakan masuk kembali.");
        }

        $session->put(self::SESSION_KEY, now()->getTimestamp());

        return $next($request);
    }

    private function isStaff(User $user): bool
    {
        return $user->getRoleNames()->contains(fn (string $role) => $role !== Role::Member->value);
    }
}
