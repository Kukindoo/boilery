<?php

namespace App\Actions\Quotes;

use App\Dtos\Quotes\QuoteDto;
use App\Models\Quote;
use App\Traits\LogsActionActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

class QuoteReassignment
{
    use LogsActionActivity;

    /**
     * @throws Throwable
     */
    public function handle(Quote $quote, QuoteDto $data): Quote
    {
        try {
            return DB::transaction(function () use ($quote, $data): Quote {
                $quote->refreshForUpdate();
                Gate::authorize('reassignment', $quote);

                $quote->update([
                    'first_name' => $data->firstName,
                    'last_name' => $data->lastName,
                    'email' => $data->email,
                    'user_id' => $data->userId,
                ]);

                $this->logActionCompleted('quotes', $quote, 'Quote reassigned');

                return $quote;
            }, attempts: 5);
        } catch (Throwable $exception) {
            $this->logActionAttempt('quotes', $quote, $exception);

            throw $exception;
        }
    }
}
