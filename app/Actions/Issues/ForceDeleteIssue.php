<?php

namespace App\Actions\Issues;

use App\Models\Issue;
use App\Traits\LogsActionActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

class ForceDeleteIssue
{
    use LogsActionActivity;

    /**
     * @throws Throwable
     */
    public function handle(Issue $issue): void
    {
        try {
            DB::transaction(function () use ($issue): void {
                $issue->refreshForUpdate();
                Gate::authorize('forceDelete', $issue);

                $issue->forceDelete();

                $this->logActionCompleted('issues', $issue, 'Issue permanently deleted');
            }, attempts: 5);
        } catch (Throwable $exception) {
            $this->logActionAttempt('issues', $issue, $exception);

            throw $exception;
        }
    }
}
