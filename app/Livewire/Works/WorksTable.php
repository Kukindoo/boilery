<?php

namespace App\Livewire\Works;

use App\Actions\Works\DeleteWork;
use App\Models\Work;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class WorksTable extends Component
{
    use WithPagination;

    public string $search = '';

    #[Locked]
    public ?int $deletingId = null;

    public bool $confirmingDeletion = false;

    public string $sortBy = 'created_at';

    public string $sortDirection = 'desc';

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

    /** @return LengthAwarePaginator<int, Work> */
    #[Computed]
    public function works(): LengthAwarePaginator
    {
        Gate::authorize('viewAny', Work::class);

        abort_unless(in_array($this->sortBy, $this->sortableColumns(), true), 422);
        abort_unless(in_array($this->sortDirection, ['asc', 'desc'], true), 422);

        return Work::query()
            ->with('user')
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query->where('order_id', 'like', '%' . $this->search . '%')
                        ->orWhere('type_of_product', 'like', '%' . $this->search . '%')
                        ->orWhereHas('user', fn ($query) => $query->where('name', 'like', '%' . $this->search . '%'));
                });
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->orderBy('id', $this->sortDirection)
            ->paginate(15);
    }

    public function openWork(Work $work): void
    {
        Gate::authorize('view', $work);

        $this->redirect(route('works.show', $work));
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $id): void
    {
        $work = Work::query()->findOrFail($id);
        Gate::authorize('delete', $work);
        $this->deletingId = $id;
        $this->confirmingDeletion = true;
    }

    public function delete(): void
    {
        abort_if($this->deletingId === null, 404);
        $work = Work::query()->findOrFail($this->deletingId);
        app(DeleteWork::class)->handle($work);
        $this->reset('deletingId', 'confirmingDeletion');
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.works-table');
    }

    /** @return list<string> */
    private function sortableColumns(): array
    {
        return ['order_id', 'type_of_product', 'work_accepted_at', 'work_completed_at', 'created_at'];
    }
}
