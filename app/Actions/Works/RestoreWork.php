<?php

namespace App\Actions\Works;

use App\Models\Work;
use App\Traits\LogsActionActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

class RestoreWork
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
                Gate::authorize('restore', $work);
                $work->restore();
                $this->logActionCompleted('works', $work, 'Work restored');
            }, attempts: 5);
        } catch (Throwable $exception) {
            $this->logActionAttempt('works', $work, $exception);

            throw $exception;
        }
    }
}
