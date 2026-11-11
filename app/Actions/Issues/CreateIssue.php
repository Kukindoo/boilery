<?php

namespace App\Actions\Issues;

use App\Dtos\Issues\IssueDto;
use App\Models\Issue;
use App\Traits\LogsActionActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

class CreateIssue
{
    use LogsActionActivity;

    /**
     * @throws Throwable
     */
    public function handle(IssueDto $data): Issue
    {
        try {
            return DB::transaction(function () use ($data): Issue {
                Gate::authorize('create', Issue::class);

                $issue = Issue::create($data->toArray());

                $this->logActionCompleted('issues', $issue, 'Issue created');

                return $issue;
            }, attempts: 5);
        } catch (Throwable $exception) {
            $this->logActionAttempt('issues', null, $exception);

            throw $exception;
        }
    }
}
