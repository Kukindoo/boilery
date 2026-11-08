<?php

namespace App\Actions\SpareParts;

use App\Dtos\SpareParts\SparePartDto;
use App\Models\SparePart;
use App\Traits\LogsActionActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

class CreateSparePart
{
    use LogsActionActivity;

    /**
     * @throws Throwable
     */
    public function handle(SparePartDto $data): SparePart
    {
        try {
            return DB::transaction(function () use ($data): SparePart {
                Gate::authorize('create', SparePart::class);
                $sparePart = SparePart::create($data->toArray());
                $this->logActionCompleted('spare_parts', $sparePart, 'Spare part created');

                return $sparePart;
            }, attempts: 5);
        } catch (Throwable $exception) {
            $this->logActionAttempt('spare_parts', null, $exception);

            throw $exception;
        }
    }
}
