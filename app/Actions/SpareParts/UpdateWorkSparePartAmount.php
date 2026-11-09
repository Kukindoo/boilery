<?php

namespace App\Actions\SpareParts;

use App\Models\SparePart;
use App\Models\Work;
use App\Traits\LogsActionActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

class UpdateWorkSparePartAmount
{
    use LogsActionActivity;

    /**
     * @throws Throwable
     */
    public function handle(Work $work, SparePart $sparePart, int $amount): void
    {
        try {
            DB::transaction(function () use ($work, $sparePart, $amount): void {
                $work->refreshForUpdate();
                Gate::authorize('manageSpareParts', $work);
                $sparePart = $work->spareParts()->lockForUpdate()->findOrFail($sparePart->id);

                $oldAmount = (int) $sparePart->pivot->getAttribute('amount');
                $work->spareParts()->updateExistingPivot($sparePart->id, ['amount' => $amount]);
                $this->logActionCompleted('spare_parts', $sparePart, 'Spare part amount updated', [
                    'work_id' => $work->id, 'old_amount' => $oldAmount, 'amount' => $amount,
                ]);
            }, attempts: 5);
        } catch (Throwable $exception) {
            $this->logActionAttempt('spare_parts', $sparePart, $exception, ['work_id' => $work->id, 'amount' => $amount]);

            throw $exception;
        }
    }
}
