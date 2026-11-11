<?php

namespace App\Actions\Issues;

use App\Actions\Works\GenerateOrderNumber;
use App\Enums\BoilerManufacturers;
use App\Enums\WorkStatus;
use App\Models\Issue;
use App\Models\Work;
use App\Traits\LogsActionActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

class CreateIssueWork
{
    use LogsActionActivity;

    /** @throws Throwable */
    public function handle(Issue $issue): Work
    {
        try {
            return DB::transaction(function () use ($issue): Work {
                $issue = Issue::query()->lockForUpdate()->findOrFail($issue->id);
                Gate::authorize('update', $issue);
                Gate::authorize('create', Work::class);

                $record = $issue->works()->create([
                    'order_id' => app(GenerateOrderNumber::class)->handle(BoilerManufacturers::UNKNOWN),
                    'status' => WorkStatus::NEW,
                    'warranty_accepted' => false,
                ]);

                $this->logActionCompleted('works', $record, 'Work created');

                return $record;
            }, attempts: 5);
        } catch (Throwable $exception) {
            $this->logActionAttempt('works', null, $exception, ['issue_id' => $issue->id]);

            throw $exception;
        }
    }
}
