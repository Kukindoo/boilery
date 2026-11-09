<?php

namespace App\Http\Controllers\Roles;

use App\Enums\Permissions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\RoleCreateRequest;
use App\Http\Requests\Roles\RoleEditRequest;
use App\Http\Requests\Roles\RoleIndexRequest;
use App\Http\Requests\Roles\RoleShowRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(RoleIndexRequest $request): View
    {
        return view('roles.index');
    }

    public function create(RoleCreateRequest $request): View
    {
        return view('roles.create');
    }

    public function show(RoleShowRequest $request, Role $role): View
    {
        $role->load('permissions');

        $permissionGroups = $role->permissions->sortBy('name')->groupBy(function (Permission $permission): string {
            $enum = Permissions::fromLabel($permission->name);

            return $enum?->modelType() ?? 'other';
        })->sortKeys();

        $permissionScopes = $permissionGroups->map(fn (Collection $permissions): Collection => $permissions->groupBy(
            fn (Permission $permission): string => Permissions::fromLabel($permission->name)?->scope() ?? 'other',
        ));

        return view('roles.show', compact('role', 'permissionGroups', 'permissionScopes'));
    }

    public function edit(RoleEditRequest $request, Role $role): View
    {
        return view('roles.edit', compact('role'));
    }
}
