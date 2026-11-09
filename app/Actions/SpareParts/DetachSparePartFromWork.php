<?php

namespace App\Actions\SpareParts;

use App\Models\SparePart;
use App\Models\Work;
use App\Traits\LogsActionActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

class DetachSparePartFromWork
{
    use LogsActionActivity;

    /**
     * @throws Throwable
     */
    public function handle(Work $work, SparePart $sparePart): void
    {
        try {
            DB::transaction(function () use ($work, $sparePart): void {
                $work->refreshForUpdate();
                Gate::authorize('manageSpareParts', $work);

                $sparePart = $work->spareParts()->lockForUpdate()->findOrFail($sparePart->id);
                $oldAmount = (int) $sparePart->pivot->getAttribute('amount');
                $work->spareParts()->detach($sparePart->id);

                $this->logActionCompleted('spare_parts', $sparePart, 'Spare part detached from work', [
                    'work_id' => $work->id, 'old_amount' => $oldAmount, 'amount' => 0,
                ]);
            }, attempts: 5);
        } catch (Throwable $exception) {
            $this->logActionAttempt('spare_parts', $sparePart, $exception, ['work_id' => $work->id]);

            throw $exception;
        }
    }
}
