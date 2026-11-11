<?php

namespace App\Actions\Issues;

use App\Dtos\Issues\IssueDto;
use App\Models\Issue;
use App\Traits\LogsActionActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

class UpdateIssue
{
    use LogsActionActivity;

    /**
     * @throws Throwable
     */
    public function handle(Issue $issue, IssueDto $data): Issue
    {
        try {
            return DB::transaction(function () use ($issue, $data): Issue {
                $issue->refreshForUpdate();
                Gate::authorize('update', $issue);

                $issue->update($data->toArray());

                $this->logActionCompleted('issues', $issue, 'Issue updated');

                return $issue;
            }, attempts: 5);
        } catch (Throwable $exception) {
            $this->logActionAttempt('issues', $issue, $exception);

            throw $exception;
        }
    }
}
