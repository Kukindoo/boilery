<?php

namespace App\Actions\Issues;

use App\Enums\QuoteStatus;
use App\Models\Issue;
use App\Models\Quote;
use App\Traits\LogsActionActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Throwable;

class CreateIssueQuote
{
    use LogsActionActivity;

    /** @throws Throwable */
    public function handle(Issue $issue): Quote
    {
        try {
            return DB::transaction(function () use ($issue): Quote {
                $issue = Issue::query()->lockForUpdate()->findOrFail($issue->id);
                Gate::authorize('update', $issue);
                Gate::authorize('create', Quote::class);

                $customer = $issue->user()->firstOrFail();
                [$firstName, $lastName] = array_pad(explode(' ', Str::squish($customer->name), 2), 2, '');
                $record = $issue->quotes()->create([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $customer->email,
                    'phone' => $customer->phone ?? '',
                    'address' => $customer->address,
                    'message' => '',
                    'status' => QuoteStatus::NEW,
                    'under_warranty' => false,
                ]);

                $this->logActionCompleted('quotes', $record, 'Quote created');

                return $record;
            }, attempts: 5);
        } catch (Throwable $exception) {
            $this->logActionAttempt('quotes', null, $exception, ['issue_id' => $issue->id]);

            throw $exception;
        }
    }
}
