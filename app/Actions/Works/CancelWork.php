<?php

namespace App\Actions\Works;

use App\Models\Work;
use App\Traits\LogsActionActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

class CancelWork
{
    use LogsActionActivity;

    /**
     * @throws Throwable
     */
    public function handle(Work $work): void
    {
        try {
            DB::transaction(function () use ($work): void {
                $work->refreshForUpdate();
                Gate::authorize('cancel', $work);
                $work->cancel();

                $this->logActionCompleted('works', $work, 'Work cancelled');
            }, attempts: 5);
        } catch (Throwable $exception) {
            $this->logActionAttempt('works', $work, $exception);

            throw $exception;
        }
    }
}
