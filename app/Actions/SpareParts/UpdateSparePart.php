<?php

namespace App\Actions\SpareParts;

use App\Dtos\SpareParts\SparePartDto;
use App\Models\SparePart;
use App\Traits\LogsActionActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

class UpdateSparePart
{
    use LogsActionActivity;

    /**
     * @throws Throwable
     */
    public function handle(SparePart $sparePart, SparePartDto $data): SparePart
    {
        try {
            return DB::transaction(function () use ($sparePart, $data): SparePart {
                $sparePart->refreshForUpdate();
                Gate::authorize('update', $sparePart);
                $sparePart->update($data->toArray());
                $this->logActionCompleted('spare_parts', $sparePart, 'Spare part updated');

                return $sparePart;
            }, attempts: 5);
        } catch (Throwable $exception) {
            $this->logActionAttempt('spare_parts', $sparePart, $exception);

            throw $exception;
        }
    }
}
