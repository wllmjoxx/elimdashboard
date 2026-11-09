<?php

namespace App\Modules\Core\Http\Middleware;

use App\Modules\Core\Support\CurrentChurch;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Switches branch scoping on for the length of a web request. Added to the
 * "web" group by CoreServiceProvider.
 */
class ScopeToCurrentChurch
{
    public function __construct(private readonly CurrentChurch $church) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->church->restrict();

        try {
            return $next($request);
        } finally {
            $this->church->release();
        }
    }
}
