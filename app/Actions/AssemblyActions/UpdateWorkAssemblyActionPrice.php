<?php

namespace App\Actions\AssemblyActions;

use App\Models\AssemblyAction;
use App\Models\Work;
use App\Traits\LogsActionActivity;
use App\ValueObjects\Price;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

class UpdateWorkAssemblyActionPrice
{
    use LogsActionActivity;

    /** @throws Throwable */
    public function handle(Work $work, AssemblyAction $assemblyAction, Price $price): void
    {
        try {
            DB::transaction(function () use ($work, $assemblyAction, $price): void {
                $work->refreshForUpdate();
                Gate::authorize('manageAssemblyActions', $work);

                $assemblyAction = $work->assemblyActions()->lockForUpdate()->findOrFail($assemblyAction->id);
                $oldPriceInCents = (int) $assemblyAction->pivot->getAttribute('price_in_cents');
                $work->assemblyActions()->updateExistingPivot($assemblyAction->id, ['price_in_cents' => $price->inCents()]);

                $this->logActionCompleted('assembly_actions', $assemblyAction, 'Assembly action price updated', [
                    'work_id' => $work->id, 'old_price_in_cents' => $oldPriceInCents, 'price_in_cents' => $price->inCents(),
                ]);
            }, attempts: 5);
        } catch (Throwable $exception) {
            $this->logActionAttempt('assembly_actions', $assemblyAction, $exception, ['work_id' => $work->id, 'price_in_cents' => $price->inCents()]);

            throw $exception;
        }
    }
}
