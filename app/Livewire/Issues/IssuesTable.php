<?php

namespace App\Livewire\Issues;

use App\Enums\IssueStatus;
use App\Enums\Permissions;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpKernel\Exception\HttpException;

class IssuesTable extends Component
{
    use WithPagination;

    #[Locked]
    public ?User $customer = null;

    public string $search = '';

    /** @var list<string> */
    public array $selectedStatuses = [IssueStatus::OPEN->value];

    public string $sortBy = 'id';

    public string $sortDirection = 'asc';

    /**
     * @throws AuthorizationException
     */
    public function mount(?User $customer = null): void
    {
        $this->customer = $customer;
        if ($customer !== null) {
            Gate::authorize('view', $customer);

            $this->selectedStatuses = array_map(fn (IssueStatus $status) => $status->value, IssueStatus::cases());
            $this->sortDirection = 'desc';
        } else {
            Gate::authorize('viewAny', Issue::class);
        }
    }

    /**
     * @return LengthAwarePaginator<int, Issue>
     *
     * @throws AuthorizationException
     * @throws QueryException
     */
    #[Computed]
    public function issues(): LengthAwarePaginator
    {
        if ($this->customer !== null) {
            Gate::authorize('view', $this->customer);
        } else {
            Gate::authorize('viewAny', Issue::class);
        }

        $column = in_array($this->sortBy, ['id', 'created_at'], true) ? $this->sortBy : 'id';
        $direction = $this->sortDirection === 'desc' ? 'desc' : 'asc';
        $ownerId = $this->customer !== null ? $this->customer->id : auth()->id();

        return Issue::query()
            ->with('user')
            ->whereIn('status', $this->selectedStatuses)
            ->when($this->customer !== null || ! auth()->user()->can(Permissions::ISSUE_VIEW_ALL->label()),
                fn (Builder $query) => $query->where('user_id', $ownerId))
            ->when($this->search !== '', function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query->where('id', 'like', '%' . $this->search . '%')
                        ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', '%' . $this->search . '%'));
                });
            })->orderBy($column, $direction)->orderBy('id')->paginate(
                $this->customer === null ? 15 : 10,
                pageName: $this->customer === null ? 'page' : 'issues_page',
            );
    }

    /**
     * @throws HttpException
     */
    public function sort(string $column): void
    {
        abort_unless(in_array($column, ['id', 'created_at'], true), 422);
        $this->sortDirection = $this->sortBy === $column && $this->sortDirection === 'asc' ? 'desc' : 'asc';
        $this->sortBy = $column;
        $this->resetIssuesPage();
    }

    public function updatedSelectedStatuses(): void
    {
        $this->resetIssuesPage();
    }

    public function updatedSearch(): void
    {
        $this->resetIssuesPage();
    }

    public function resetIssuesPage(): void
    {
        $this->resetPage(pageName: $this->customer === null ? 'page' : 'issues_page');
    }

    public function render(): View
    {
        return view('livewire.issues.issues-table');
    }
}
