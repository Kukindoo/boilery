<?php

namespace App\Actions\AssemblyActions;

use App\Models\AssemblyAction;
use App\Models\Work;
use App\Traits\LogsActionActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

class DetachAssemblyActionFromWork
{
    use LogsActionActivity;

    /**
     * @throws Throwable
     */
    public function handle(Work $work, AssemblyAction $assemblyAction): void
    {
        try {
            DB::transaction(function () use ($work, $assemblyAction): void {
                $work->refreshForUpdate();
                Gate::authorize('manageAssemblyActions', $work);
                Gate::authorize('viewAny', AssemblyAction::class);

                $assemblyAction = $work->assemblyActions()->lockForUpdate()->findOrFail($assemblyAction->id);
                $work->assemblyActions()->detach($assemblyAction->id);

                $this->logActionCompleted('assembly_actions', $assemblyAction, 'Assembly action detached from work', [
                    'work_id' => $work->id,
                ]);
            }, attempts: 5);
        } catch (Throwable $exception) {
            $this->logActionAttempt('assembly_actions', $assemblyAction, $exception, ['work_id' => $work->id]);

            throw $exception;
        }
    }
}
