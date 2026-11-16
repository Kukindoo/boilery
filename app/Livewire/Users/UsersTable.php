<?php

namespace App\Livewire\Users;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class UsersTable extends Component
{
    use WithPagination;

    public string $search = '';

    /** @var list<string> */
    public array $selectedRoles = [];

    public string $sortBy = 'name';

    public string $sortDirection = 'asc';

    public function mount(): void
    {
        $this->selectedRoles = [...$this->roles()->map(fn (Role $role): string => (string) $role->id)->all(), 'none'];
    }

    public function sort(string $column): void
    {
        abort_unless(in_array($column, $this->sortableColumns(), true), 422);

        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    /** @return LengthAwarePaginator<int, User> */
    #[Computed]
    public function users(): LengthAwarePaginator
    {
        Gate::authorize('viewAny', User::class);
        abort_unless(in_array($this->sortBy, $this->sortableColumns(), true), 422);
        abort_unless(in_array($this->sortDirection, ['asc', 'desc'], true), 422);

        return User::query()
            ->with('roles')
            ->where(function ($query): void {
                $query->whereHas('roles', fn ($query) => $query
                    ->whereIn('roles.id', array_values(array_filter($this->selectedRoles, fn (string $role): bool => $role !== 'none')))
                    ->where('guard_name', 'web'));

                if (in_array('none', $this->selectedRoles, true)) {
                    $query->orWhereDoesntHave('roles');
                }
            })
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%')
                        ->orWhere('phone', 'like', '%' . $this->search . '%')
                        ->orWhere('address', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->orderBy('id', $this->sortDirection)
            ->paginate(15);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedRoles(): void
    {
        $this->resetPage();
    }

    /** @return Collection<int, Role> */
    #[Computed]
    public function roles(): Collection
    {
        Gate::authorize('viewAny', User::class);

        return Role::query()->where('guard_name', 'web')->orderBy('name')->get();
    }

    public function render(): View
    {
        return view('livewire.users.users-table');
    }

    /** @return list<string> */
    private function sortableColumns(): array
    {
        return ['name', 'email', 'phone', 'created_at'];
    }
}
