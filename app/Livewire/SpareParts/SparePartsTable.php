<?php

namespace App\Livewire\SpareParts;

use App\Actions\SpareParts\DeleteSparePart;
use App\Models\SparePart;
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

class SparePartsTable extends Component
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
        Gate::authorize('viewAny', SparePart::class);
    }

    /**
     * @return LengthAwarePaginator<int, SparePart>
     *
     * @throws AuthorizationException
     * @throws QueryException
     */
    #[Computed]
    public function spareParts(): LengthAwarePaginator
    {
        Gate::authorize('viewAny', SparePart::class);

        $column = in_array($this->sortBy, ['name', 'part_number', 'created_at'], true) ? $this->sortBy : 'name';
        $direction = $this->sortDirection === 'desc' ? 'desc' : 'asc';

        return SparePart::query()
            ->withExists(['works' => fn (Builder $query) => $query->withoutGlobalScope(SoftDeletingScope::class)])
            ->when($this->search !== '', function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query->where('name', 'like', '%' . $this->search . '%')->orWhere('part_number', 'like', '%' . $this->search . '%');
                });
            })->orderBy($column, $direction)->orderBy('id')->paginate(15);
    }

    /**
     * @throws HttpException
     */
    public function sort(string $column): void
    {
        abort_unless(in_array($column, ['name', 'part_number', 'created_at'], true), 422);
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
        $sparePart = SparePart::findOrFail($id);
        Gate::authorize('delete', $sparePart);

        $this->deletingId = $id;
        $this->confirmingDeletion = true;
    }

    /**
     * @throws Throwable
     */
    public function delete(): void
    {
        abort_if($this->deletingId === null, 404);
        $sparePart = SparePart::findOrFail($this->deletingId);
        app(DeleteSparePart::class)->handle($sparePart);
        $this->reset('deletingId', 'confirmingDeletion');
        $this->resetPage();
        unset($this->spareParts);
    }

    public function render(): View
    {
        return view('livewire.spare-parts.spare-parts-table');
    }
}
