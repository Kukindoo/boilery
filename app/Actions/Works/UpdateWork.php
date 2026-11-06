<?php

namespace App\Actions\Works;

use App\Dtos\Works\WorkDto;
use App\Models\Work;
use App\Traits\LogsActionActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

class UpdateWork
{
    use LogsActionActivity;

    /**
     * @throws Throwable
     */
    public function handle(Work $work, WorkDto $data): Work
    {
        try {
            return DB::transaction(function () use ($work, $data): Work {
                $work->refreshForUpdate();
                Gate::authorize('update', $work);

                $work->update($data->toArray());

                $this->logActionCompleted('works', $work, 'Work updated');

                return $work;
            }, attempts: 5);
        } catch (Throwable $exception) {
            $this->logActionAttempt('works', $work, $exception);

            throw $exception;
        }
    }
}
