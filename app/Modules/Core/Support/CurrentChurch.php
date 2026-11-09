<?php

namespace App\Modules\Core\Support;

use App\Models\User;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\Church;
use Closure;
use Illuminate\Contracts\Auth\Factory as AuthFactory;

/**
 * Decides which branch the current code works in (PRD 4.10).
 *
 * Scoping is only switched on while a web request runs (ScopeToCurrentChurch
 * middleware). Inside a request, visitors and users without a branch see the
 * default branch, staff and members see their own branch, and Superadmin sees
 * every branch. Console commands, queued jobs and seeders run unscoped.
 */
final class CurrentChurch
{
    private bool $restricted = false;

    private ?int $defaultId = null;

    private bool $defaultResolved = false;

    public function __construct(private readonly AuthFactory $auth) {}

    /**
     * Switch scoping on (start of a web request).
     */
    public function restrict(): void
    {
        $this->restricted = true;
    }

    /**
     * Switch scoping off again (end of a web request).
     */
    public function release(): void
    {
        $this->restricted = false;
        $this->defaultResolved = false;
        $this->defaultId = null;
    }

    /**
     * Whether queries on branch tables must be limited to id().
     */
    public function restricts(): bool
    {
        return $this->restricted && ! $this->isSuperadmin($this->user());
    }

    /**
     * The branch to limit queries to, or null when none applies (unscoped,
     * Superadmin, or no default branch exists yet).
     */
    public function id(): ?int
    {
        if (! $this->restricts()) {
            return null;
        }

        return $this->user()?->church_id ?? $this->defaultId();
    }

    /**
     * The branch new records get when they are created without one.
     */
    public function idForNewRecords(): ?int
    {
        return $this->id() ?? $this->user()?->church_id ?? $this->defaultId();
    }

    /**
     * Run a callback with scoping switched off, e.g. for a Superadmin report
     * or a lookup that must see every branch.
     *
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public function withoutScope(Closure $callback): mixed
    {
        $restricted = $this->restricted;
        $this->restricted = false;

        try {
            return $callback();
        } finally {
            $this->restricted = $restricted;
        }
    }

    private function user(): ?User
    {
        $user = $this->auth->guard()->user();

        return $user instanceof User ? $user : null;
    }

    private function isSuperadmin(?User $user): bool
    {
        return $user !== null && $user->hasRole(Role::Superadmin->value);
    }

    private function defaultId(): ?int
    {
        if (! $this->defaultResolved) {
            $this->defaultId = Church::default()?->id;
            $this->defaultResolved = true;
        }

        return $this->defaultId;
    }
}
