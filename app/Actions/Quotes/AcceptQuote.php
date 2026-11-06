<?php

namespace App\Actions\Quotes;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Traits\LogsActionActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Throwable;

class AcceptQuote
{
    use LogsActionActivity;

    /**
     * @throws Throwable
     */
    public function handle(Quote $quote): Quote
    {
        try {
            return DB::transaction(function () use ($quote): Quote {
                $quote->refreshForUpdate();
                Gate::authorize('accept', $quote);

                if ($quote->user_id === null) {
                    throw ValidationException::withMessages([
                        'quote' => 'A quote must have a customer before it can be accepted.',
                    ]);
                }

                if ($quote->status !== QuoteStatus::ACCEPTED) {
                    $quote->update(['status' => QuoteStatus::ACCEPTED]);

                    $this->logActionCompleted('quotes', $quote, 'Quote accepted');
                }

                $work = $quote->work()->withTrashed()->firstOrCreate([], [
                    'user_id' => $quote->user_id,
                    'order_id' => 'QUOTE-' . $quote->id,
                    'boiler_manufacturer' => $quote->boiler_manufacturer,
                    'type_number' => $quote->boiler_type,
                    'serial_number' => $quote->boiler_serial_number,
                    'reported_problem' => $quote->message,
                    'work_accepted_at' => now(),
                ]);

                $quote->files()->update(['work_id' => $work->id]);

                if ($work->wasRecentlyCreated) {
                    $this->logActionCompleted('works', $work, 'Work created from accepted quote', ['quote_id' => $quote->id]);
                }

                return $quote->fresh()->load('work');
            });
        } catch (Throwable $exception) {
            $this->logActionAttempt('quotes', $quote, $exception);

            throw $exception;
        }
    }
}
