<?php

namespace App\Actions\Issues;

use App\Models\Issue;
use App\Traits\LogsActionActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

class DeleteIssue
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
                Gate::authorize('delete', $issue);

                $issue->delete();

                $this->logActionCompleted('issues', $issue, 'Issue deleted');
            }, attempts: 5);
        } catch (Throwable $exception) {
            $this->logActionAttempt('issues', $issue, $exception);

            throw $exception;
        }
    }
}
