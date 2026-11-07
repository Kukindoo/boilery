<?php

namespace App\Actions\Quotes;

use App\Models\Quote;
use App\Traits\LogsActionActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

class RestoreQuote
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
                Gate::authorize('restore', $quote);
                $quote->restore();
                $this->logActionCompleted('quotes', $quote, 'Quote restored');
            }, attempts: 5);
        } catch (Throwable $exception) {
            $this->logActionAttempt('quotes', $quote, $exception);

            throw $exception;
        }
    }
}
