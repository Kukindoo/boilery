<?php

namespace App\Actions\Quotes;

use App\Dtos\Quotes\QuoteDto;
use App\Models\Quote;
use App\Traits\LogsActionActivity;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

class UpdateQuote
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
                Gate::authorize('update', $quote);

                $quote->update(Arr::only($data->toArray(), [
                    'message',
                    'status',
                    'under_warranty',
                    'boiler_manufacturer',
                    'boiler_serial_number',
                    'boiler_type',
                ]));

                $this->logActionCompleted('quotes', $quote, 'Quote updated');

                return $quote;
            }, attempts: 5);
        } catch (Throwable $exception) {
            $this->logActionAttempt('quotes', $quote, $exception);

            throw $exception;
        }
    }
}
