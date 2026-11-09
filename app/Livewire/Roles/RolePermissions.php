<?php

namespace App\Livewire\Roles;

use App\Actions\Roles\UpdateRolePermissions;
use App\Enums\Permissions;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Throwable;

class RolePermissions extends Component
{
    #[Locked]
    public Role $role;

    #[Locked]
    public bool $editing = false;

    /** @var list<int> */
    #[Locked]
    public array $permissionIds = [];

    public function mount(Role $role): void
    {
        Gate::authorize('view', $role);
        $this->role = $role;
    }

    public function edit(): void
    {
        Gate::authorize('managePermissions', $this->role);

        $this->permissionIds = $this->role->permissions()->pluck('permissions.id')->all();
        $this->resetValidation();
        $this->editing = true;
    }

    public function cancel(): void
    {
        $this->editing = false;
        $this->permissionIds = [];
        $this->resetValidation();
    }

    public function togglePermission(int $id): void
    {
        Gate::authorize('managePermissions', $this->role);
        abort_unless($this->editing, 403);
        Permission::query()->where('guard_name', 'web')->findOrFail($id);
        if (in_array($id, $this->permissionIds, true)) {
            $this->permissionIds = array_values(array_diff($this->permissionIds, [$id]));
        } else {
            $this->permissionIds[] = $id;
        }
    }

    /**
     * @throws Throwable
     */
    public function save(): void
    {
        Gate::authorize('managePermissions', $this->role);
        abort_unless($this->editing, 403);
        $this->validate([
            'permissionIds' => ['array'],
            'permissionIds.*' => ['integer', 'distinct', Rule::exists('permissions', 'id')->where('guard_name', 'web')],
        ]);
        app(UpdateRolePermissions::class)->handle($this->role, $this->permissionIds);
        $this->role->unsetRelation('permissions');
        $this->cancel();
    }

    public function render(): View
    {
        Gate::authorize('view', $this->role);
        $assignedIds = $this->role->permissions()->pluck('permissions.id')->all();
        $selectedIds = $this->editing ? $this->permissionIds : $assignedIds;
        $permissions = Permission::query()->where('guard_name', 'web')
            ->when(! $this->editing, fn ($query) => $query->whereIn('id', $assignedIds))
            ->orderBy('name')->get()->sortBy(fn (Permission $permission): int => match (true) {
                in_array($permission->id, $selectedIds, true) => 0,
                in_array($permission->id, $assignedIds, true) => 1,
                default => 2,
            });
        $permissionGroups = $permissions->groupBy(fn (Permission $permission): string => Permissions::fromLabel($permission->name)?->modelType() ?? 'other')->sortKeys();
        $permissionScopes = $permissionGroups->map(fn (Collection $permissions): Collection => $permissions->groupBy(
            fn (Permission $permission): string => Permissions::fromLabel($permission->name)?->scope() ?? 'other',
        ));

        return view('livewire.roles.role-permissions', compact('permissionGroups', 'permissionScopes', 'assignedIds'));
    }
}
