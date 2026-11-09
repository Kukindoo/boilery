<?php

namespace App\Actions\AssemblyActions;

use App\Models\AssemblyAction;
use App\Traits\LogsActionActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

class DeleteAssemblyAction
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
                Gate::authorize('delete', $assemblyAction);

                $assemblyAction->delete();

                $this->logActionCompleted('assembly_actions', $assemblyAction, 'Assembly action deleted');
            }, attempts: 5);
        } catch (Throwable $exception) {
            $this->logActionAttempt('assembly_actions', $assemblyAction, $exception);

            throw $exception;
        }
    }
}
