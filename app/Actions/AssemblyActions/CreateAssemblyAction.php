<?php

namespace App\Actions\AssemblyActions;

use App\Dtos\AssemblyActions\AssemblyActionDto;
use App\Models\AssemblyAction;
use App\Traits\LogsActionActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

class CreateAssemblyAction
{
    use LogsActionActivity;

    /**
     * @throws Throwable
     */
    public function handle(AssemblyActionDto $data): AssemblyAction
    {
        try {
            return DB::transaction(function () use ($data): AssemblyAction {
                Gate::authorize('create', AssemblyAction::class);

                $assemblyAction = AssemblyAction::create($data->toArray());

                $this->logActionCompleted('assembly_actions', $assemblyAction, 'Assembly action created');

                return $assemblyAction;
            }, attempts: 5);
        } catch (Throwable $exception) {
            $this->logActionAttempt('assembly_actions', null, $exception);

            throw $exception;
        }
    }
}
