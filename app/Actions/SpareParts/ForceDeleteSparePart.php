<?php

namespace App\Actions\SpareParts;

use App\Models\SparePart;
use App\Traits\LogsActionActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

class ForceDeleteSparePart
{
    use LogsActionActivity;

    /**
     * @throws Throwable
     */
    public function handle(SparePart $sparePart): void
    {
        try {
            DB::transaction(function () use ($sparePart): void {
                $sparePart->refreshForUpdate();
                Gate::authorize('forceDelete', $sparePart);

                $sparePart->forceDelete();
                $this->logActionCompleted('spare_parts', $sparePart, 'Spare part permanently deleted');
            }, attempts: 5);
        } catch (Throwable $exception) {
            $this->logActionAttempt('spare_parts', $sparePart, $exception);

            throw $exception;
        }
    }
}
