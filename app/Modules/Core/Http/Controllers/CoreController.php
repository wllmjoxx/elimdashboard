<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\Church;
use App\Modules\Core\Support\CurrentChurch;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role as SpatieRole;

class CoreController extends Controller
{
    /**
     * Branches the user may see (all for Superadmin, otherwise their own) and
     * the permissions of each role.
     */
    public function index(CurrentChurch $current): Response
    {
        $churches = Church::query()
            ->when($current->restricts(), fn ($query) => $query->whereKey($current->id()))
            ->withCount('users')
            ->orderBy('name')
            ->get()
            ->map(fn (Church $church) => [
                'id' => $church->id,
                'name' => $church->name,
                'slug' => $church->slug,
                'address' => $church->address,
                'whatsapp' => $church->contact['whatsapp'] ?? null,
                'email' => $church->contact['email'] ?? null,
                'timezone' => $church->timezone,
                'isDefault' => $church->slug === config('modules.core.default_church'),
                'isActive' => $church->is_active,
                'usersCount' => $church->users_count,
            ]);

        $permissions = SpatieRole::query()
            ->with('permissions:id,name')
            ->get()
            ->mapWithKeys(fn (SpatieRole $role) => [$role->name => $role->permissions->pluck('name')->sort()->values()]);

        $roles = array_map(fn (Role $role) => [
            'name' => $role->value,
            'label' => $role->label(),
            'permissions' => $role === Role::Superadmin ? ['*'] : ($permissions[$role->value] ?? []),
        ], Role::cases());

        return Inertia::render('core/index', [
            'churches' => $churches,
            'roles' => $roles,
            'canManageChurches' => request()->user()->can('core.churches.manage'),
            'canViewAudit' => request()->user()->can('core.audit.view'),
            'flash' => request()->session()->get('status'),
        ]);
    }
}
