<?php

namespace App\Livewire\ActivityLogs;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\LengthAwarePaginator;
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

    public function mount(?string $subjectType = null, int|string|null $subjectId = null): void
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
    }

    /** @return LengthAwarePaginator<int, Activity> */
    #[Computed]
    public function activities(): LengthAwarePaginator
    {
        Gate::authorize('viewAny', Activity::class);

        return Activity::query()
            ->with('causer')
            ->when($this->subjectType !== null, fn ($query) => $query->where('subject_type', $this->subjectType))
            ->when($this->subjectId !== null, fn ($query) => $query->where('subject_id', $this->subjectId))
            ->latest('created_at')
            ->latest('id')
            ->paginate(15, pageName: 'activityPage');
    }

    public function render(): View
    {
        return view('livewire.activity-logs.activity-logs-table');
    }
}
