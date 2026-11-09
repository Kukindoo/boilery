<?php

namespace App\Actions\AssemblyActions;

use App\Models\AssemblyAction;
use App\Traits\LogsActionActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

class ForceDeleteAssemblyAction
{
    use LogsActionActivity;

    /**
     * @throws Throwable
     */
    public function handle(AssemblyAction $assemblyAction): void
    {
        try {
            DB::transaction(function () use ($assemblyAction): void {
                $assemblyAction->refreshForUpdate();
                Gate::authorize('forceDelete', $assemblyAction);

                $assemblyAction->forceDelete();

                $this->logActionCompleted('assembly_actions', $assemblyAction, 'Assembly action permanently deleted');
            }, attempts: 5);
        } catch (Throwable $exception) {
            $this->logActionAttempt('assembly_actions', $assemblyAction, $exception);

            throw $exception;
        }
    }
}
