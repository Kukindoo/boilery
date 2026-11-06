<?php

namespace App\Actions\Works;

use App\Models\Work;
use App\Traits\LogsActionActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

class EndWork
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
                Gate::authorize('end', $work);
                $work->end();

                $this->logActionCompleted('works', $work, 'Work finished');
            }, attempts: 5);
        } catch (Throwable $exception) {
            $this->logActionAttempt('works', $work, $exception);

            throw $exception;
        }
    }
}
