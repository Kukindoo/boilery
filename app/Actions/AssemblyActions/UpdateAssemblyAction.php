<?php

namespace App\Actions\AssemblyActions;

use App\Dtos\AssemblyActions\AssemblyActionDto;
use App\Models\AssemblyAction;
use App\Traits\LogsActionActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

class UpdateAssemblyAction
{
    use LogsActionActivity;

    /**
     * @throws Throwable
     */
    public function handle(AssemblyAction $assemblyAction, AssemblyActionDto $data): AssemblyAction
    {
        try {
            return DB::transaction(function () use ($assemblyAction, $data): AssemblyAction {
                $assemblyAction->refreshForUpdate();
                Gate::authorize('update', $assemblyAction);

                $assemblyAction->update($data->toArray());

                $this->logActionCompleted('assembly_actions', $assemblyAction, 'Assembly action updated');

                return $assemblyAction;
            }, attempts: 5);
        } catch (Throwable $exception) {
            $this->logActionAttempt('assembly_actions', $assemblyAction, $exception);

            throw $exception;
        }
    }
}
