<?php

namespace App\Livewire\ActivityLogs;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

class ActivityLogsTable extends Component
{
    use WithPagination;

    #[Locked]
    public ?string $subjectType = null;

    #[Locked]
    public int|string|null $subjectId = null;

    #[Locked]
    public ?User $causer = null;

    public string $search = '';

    public ?string $selectedUser = null;

    public ?string $selectedModel = null;

    public ?string $selectedEvent = null;

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'selectedUser', 'selectedModel', 'selectedEvent'], true)) {
            $this->resetPage('activityPage');
        }
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'selectedUser', 'selectedModel', 'selectedEvent');
        $this->resetPage('activityPage');
    }

    /** @return Collection<int, User> */
    #[Computed]
    public function filterUsers(): Collection
    {
        return User::query()->whereIn('id', $this->scopedQuery()
            ->where('causer_type', (new User)->getMorphClass())->select('causer_id'))
            ->orderBy('name')->get(['id', 'name', 'email']);
    }

    /** @return Collection<int, string> */
    #[Computed]
    public function filterModels(): Collection
    {
        return $this->scopedQuery()->whereNotNull('subject_type')->distinct()->orderBy('subject_type')->pluck('subject_type');
    }

    /** @return Collection<int, string> */
    #[Computed]
    public function filterEvents(): Collection
    {
        return $this->scopedQuery()->whereNotNull('event')->distinct()->orderBy('event')->pluck('event');
    }

    public function mount(?string $subjectType = null, int|string|null $subjectId = null, ?User $causer = null): void
    {
        Gate::authorize('viewAny', Activity::class);

        abort_if($subjectId !== null && $subjectType === null, 422, 'A subject type is required with a subject ID.');

        if ($subjectType !== null) {
            $modelClass = Relation::getMorphedModel($subjectType) ?? $subjectType;
            abort_unless(is_subclass_of($modelClass, Model::class), 422, 'Invalid subject model.');
            $subjectType = (new $modelClass)->getMorphClass();
        }

        $this->subjectType = $subjectType;
        $this->subjectId = $subjectId;
        $this->causer = $causer;
    }

    /** @return LengthAwarePaginator<int, Activity> */
    #[Computed]
    public function activities(): LengthAwarePaginator
    {
        Gate::authorize('viewAny', Activity::class);

        return $this->scopedQuery()
            ->with(['causer', 'subject'])
            ->when($this->selectedUser !== null && $this->selectedUser !== '', fn ($query) => $query
                ->where('causer_type', (new User)->getMorphClass())->where('causer_id', $this->selectedUser))
            ->when($this->selectedModel !== null && $this->selectedModel !== '', fn ($query) => $query->where('subject_type', $this->selectedModel))
            ->when($this->selectedEvent !== null && $this->selectedEvent !== '', fn ($query) => $query->where('event', $this->selectedEvent))
            ->when(mb_trim($this->search) !== '', function (Builder $query): void {
                $search = mb_trim($this->search);
                if (ctype_digit($search)) {
                    $query->where('subject_id', $search);

                    return;
                }

                $term = '%' . $search . '%';
                $query->where(function (Builder $query) use ($term): void {
                    foreach (['description', 'log_name', 'event', 'subject_type', 'subject_id', 'causer_id', 'created_at', 'properties', 'attribute_changes'] as $column) {
                        $query->orWhere($column, 'like', $term);
                    }
                    $query->orWhereHasMorph('causer', [User::class], fn (Builder $userQuery) => $userQuery
                        ->where(fn (Builder $names) => $names->where('name', 'like', $term)->orWhere('email', 'like', $term)));
                });
            })
            ->latest('created_at')
            ->latest('id')
            ->paginate(15, pageName: 'activityPage');
    }

    public function render(): View
    {
        return view('livewire.activity-logs.activity-logs-table');
    }

    /** @return Builder<Activity> */
    private function scopedQuery(): Builder
    {
        Gate::authorize('viewAny', Activity::class);

        return Activity::query()
            ->when($this->causer !== null, fn ($query) => $query->causedBy($this->causer))
            ->when($this->subjectType !== null, fn ($query) => $query->where('subject_type', $this->subjectType))
            ->when($this->subjectId !== null, fn ($query) => $query->where('subject_id', $this->subjectId));
    }
}
