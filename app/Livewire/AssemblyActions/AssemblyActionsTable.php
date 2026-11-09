<?php

namespace App\Livewire\AssemblyActions;

use App\Models\AssemblyAction;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AssemblyActionsTable extends Component
{
    use WithPagination;

    public string $search = '';

    public string $sortBy = 'name';

    public string $sortDirection = 'asc';

    /**
     * @throws AuthorizationException
     */
    public function mount(): void
    {
        Gate::authorize('viewAny', AssemblyAction::class);
    }

    /**
     * @return LengthAwarePaginator<int, AssemblyAction>
     *
     * @throws AuthorizationException
     * @throws QueryException
     */
    #[Computed]
    public function assemblyActions(): LengthAwarePaginator
    {
        Gate::authorize('viewAny', AssemblyAction::class);

        $column = in_array($this->sortBy, ['code', 'name', 'description', 'created_at'], true) ? $this->sortBy : 'name';
        $direction = $this->sortDirection === 'desc' ? 'desc' : 'asc';

        return AssemblyAction::query()
            ->withExists(['works' => fn (Builder $query) => $query->withoutGlobalScope(SoftDeletingScope::class)])
            ->when($this->search !== '', function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('code', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })->orderBy($column, $direction)->orderBy('id')->paginate(15);
    }

    /**
     * @throws HttpException
     */
    public function sort(string $column): void
    {
        abort_unless(in_array($column, ['code', 'name', 'description', 'created_at'], true), 422);
        $this->sortDirection = $this->sortBy === $column && $this->sortDirection === 'asc' ? 'desc' : 'asc';
        $this->sortBy = $column;
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.assembly-actions.assembly-actions-table');
    }
}
