<?php

namespace App\Livewire\Customers;

use App\Enums\Roles;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class CustomersTable extends Component
{
    use WithPagination;

    public string $search = '';

    public string $sortBy = 'name';

    public string $sortDirection = 'asc';

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
    public function customers(): LengthAwarePaginator
    {
        Gate::authorize('viewAny', User::class);
        abort_unless(in_array($this->sortBy, $this->sortableColumns(), true), 422);
        abort_unless(in_array($this->sortDirection, ['asc', 'desc'], true), 422);

        return User::query()
            ->with('roles')
            ->role(Roles::CUSTOMER->label())
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

    public function render(): View
    {
        return view('livewire.customers.customers-table');
    }

    /** @return list<string> */
    private function sortableColumns(): array
    {
        return ['name', 'email', 'phone', 'created_at'];
    }
}
