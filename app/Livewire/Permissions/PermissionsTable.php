<?php

namespace App\Livewire\Permissions;

use App\Enums\Permissions;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Spatie\Permission\Models\Permission;

class PermissionsTable extends Component
{
    public string $search = '';

    public string $sortDirection = 'asc';

    public function mount(): void
    {
        Gate::authorize('viewAny', Permission::class);
    }

    /** @return Collection<int, Permission> */
    #[Computed]
    public function permissions(): Collection
    {
        Gate::authorize('viewAny', Permission::class);

        return Permission::query()->where('guard_name', 'web')
            ->where('name', 'like', '%' . $this->search . '%')
            ->withCount('roles')
            ->orderBy('name', $this->sortDirection === 'desc' ? 'desc' : 'asc')->orderBy('id')->get();
    }

    public function sort(): void
    {
        $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
    }

    public function render(): View
    {
        $permissionGroups = $this->permissions()->groupBy(
            fn (Permission $permission): string => Permissions::fromLabel($permission->name)?->modelType() ?? 'other',
        )->sortKeys();

        return view('livewire.permissions.permissions-table', compact('permissionGroups'));
    }
}
