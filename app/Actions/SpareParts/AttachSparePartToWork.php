<?php

namespace App\Actions\SpareParts;

use App\Models\SparePart;
use App\Models\Work;
use App\Traits\LogsActionActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Throwable;

class AttachSparePartToWork
{
    use LogsActionActivity;

    /**
     * @throws Throwable
     */
    public function handle(Work $work, SparePart $sparePart, int $amount = 1): void
    {
        try {
            DB::transaction(function () use ($work, $sparePart, $amount): void {
                $work->refreshForUpdate();
                Gate::authorize('manageSpareParts', $work);

                $sparePart = SparePart::query()->lockForUpdate()->findOrFail($sparePart->id);
                if (! $work->spareParts()->whereKey($sparePart->id)->exists() && $work->spareParts()->count() >= 5) {
                    throw ValidationException::withMessages(['sparePartId' => 'K zakázce lze přidat nejvýše 5 náhradních dílů.']);
                }

                $work->spareParts()->syncWithoutDetaching([$sparePart->id => ['amount' => $amount]]);
                $this->logActionCompleted('spare_parts', $sparePart, 'Spare part attached to work', ['work_id' => $work->id, 'amount' => $amount]);
            }, attempts: 5);
        } catch (Throwable $exception) {
            $this->logActionAttempt('spare_parts', $sparePart, $exception, ['work_id' => $work->id, 'amount' => $amount]);

            throw $exception;
        }
    }
}
