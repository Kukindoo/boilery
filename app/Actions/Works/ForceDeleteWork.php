<?php

namespace App\Actions\Works;

use App\Models\Work;
use App\Traits\LogsActionActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

class ForceDeleteWork
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
                Gate::authorize('forceDelete', $work);
                $work->forceDelete();
                $this->logActionCompleted('works', $work, 'Work permanently deleted');
            }, attempts: 5);
        } catch (Throwable $exception) {
            $this->logActionAttempt('works', $work, $exception);

            throw $exception;
        }
    }
}
