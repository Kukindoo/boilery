<?php

namespace App\Livewire\Roles;

use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class RolesTable extends Component
{
    use WithPagination;

    public string $search = '';

    public string $sortDirection = 'asc';

    public function mount(): void
    {
        Gate::authorize('viewAny', Role::class);
    }

    /** @return LengthAwarePaginator<int, Role> */
    #[Computed]
    public function roles(): LengthAwarePaginator
    {
        Gate::authorize('viewAny', Role::class);

        return Role::query()->where('guard_name', 'web')
            ->where('name', 'like', '%' . $this->search . '%')
            ->withCount(['permissions', 'users'])
            ->orderBy('name', $this->sortDirection === 'desc' ? 'desc' : 'asc')->orderBy('id')->paginate(15);
    }

    public function sort(): void
    {
        $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.roles.roles-table');
    }
}
