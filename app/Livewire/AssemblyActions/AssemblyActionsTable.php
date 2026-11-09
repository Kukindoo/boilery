<?php

namespace App\Livewire\AssemblyActions;

use App\Actions\AssemblyActions\DeleteAssemblyAction;
use App\Models\AssemblyAction;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class AssemblyActionsTable extends Component
{
    use WithPagination;

    public string $search = '';

    public string $sortBy = 'name';

    public string $sortDirection = 'asc';

    #[Locked]
    public ?int $deletingId = null;

    public bool $confirmingDeletion = false;

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

    /**
     * @throws AuthorizationException
     * @throws ModelNotFoundException
     * @throws QueryException
     */
    public function confirmDelete(int $id): void
    {
        $assemblyAction = AssemblyAction::query()->findOrFail($id);
        Gate::authorize('delete', $assemblyAction);

        $this->deletingId = $id;
        $this->confirmingDeletion = true;
    }

    /**
     * @throws Throwable
     */
    public function delete(): void
    {
        abort_if($this->deletingId === null, 404);
        $assemblyAction = AssemblyAction::query()->findOrFail($this->deletingId);
        app(DeleteAssemblyAction::class)->handle($assemblyAction);
        $this->reset('deletingId', 'confirmingDeletion');
        $this->resetPage();
        unset($this->assemblyActions);
    }

    public function render(): View
    {
        return view('livewire.assembly-actions.assembly-actions-table');
    }
}
