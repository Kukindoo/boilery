<?php

namespace App\Actions\Quotes;

use App\Models\Quote;
use App\Traits\LogsActionActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

class ForceDeleteQuote
{
    use LogsActionActivity;

    /**
     * @throws Throwable
     */
    public function handle(Quote $quote): void
    {
        try {
            DB::transaction(function () use ($quote): void {
                $quote->refreshForUpdate();
                Gate::authorize('forceDelete', $quote);
                $quote->forceDelete();
                $this->logActionCompleted('quotes', $quote, 'Quote permanently deleted');
            }, attempts: 5);
        } catch (Throwable $exception) {
            $this->logActionAttempt('quotes', $quote, $exception);

            throw $exception;
        }
    }
}
