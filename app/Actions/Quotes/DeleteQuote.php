<?php

namespace App\Actions\Quotes;

use App\Models\Quote;
use App\Traits\LogsActionActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

class DeleteQuote
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
                Gate::authorize('delete', $quote);
                $quote->delete();
                $this->logActionCompleted('quotes', $quote, 'Quote deleted');
            }, attempts: 5);
        } catch (Throwable $exception) {
            $this->logActionAttempt('quotes', $quote, $exception);

            throw $exception;
        }
    }
}
