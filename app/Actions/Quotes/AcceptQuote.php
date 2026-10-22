<?php

namespace App\Actions\Quotes;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Throwable;

class AcceptQuote
{
    /**
     * @throws Throwable
     */
    public function handle(Quote $quote): Quote
    {
        Gate::authorize('accept', $quote);

        return DB::transaction(function () use ($quote): Quote {
            $quote->refreshForUpdate();

            if ($quote->user_id === null) {
                activity('quotes')
                    ->performedOn($quote)
                    ->log('A quote must have a customer before it can be accepted.');

                throw ValidationException::withMessages([
                    'quote' => 'A quote must have a customer before it can be accepted.',
                ]);
            }

            $quote->update(['status' => QuoteStatus::ACCEPTED]);

            if ($quote->wasChanged('status')) {
                activity('quotes')
                    ->performedOn($quote)
                    ->log('Quote accepted');
            }

            $work = $quote->work()->withTrashed()->firstOrCreate([], [
                'user_id' => $quote->user_id,
                'order_id' => 'QUOTE-' . $quote->id,
                'type_of_product' => $quote->boiler_manufacturer?->value,
                'type_number' => $quote->boiler_type,
                'serial_number' => $quote->boiler_serial_number,
                'reported_problem' => $quote->message,
                'work_accepted_at' => now(),
            ]);

            if ($work->wasRecentlyCreated) {
                activity('works')
                    ->performedOn($work)
                    ->withProperties(['quote_id' => $quote->id])
                    ->log('Work created from accepted quote');
            }

            return $quote->load('work');
        });
    }
}
