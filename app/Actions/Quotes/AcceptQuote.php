<?php

namespace App\Actions\Quotes;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\Work;
use App\Traits\LogsActionActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

class AcceptQuote
{
    use LogsActionActivity;

    /**
     * @throws Throwable
     */
    public function handle(Quote $quote): Work
    {
        try {
            return DB::transaction(function () use ($quote): Work {
                $quote->refreshForUpdate();
                Gate::authorize('accept', $quote);

                if ($quote->status !== QuoteStatus::ACCEPTED) {
                    $quote->update(['status' => QuoteStatus::ACCEPTED]);

                    $this->logActionCompleted('quotes', $quote, 'Quote accepted');
                }

                $issue = $quote->issue;

                $work = $issue->works()->create([
                    'order_id' => 'QUOTE-' . $quote->id,
                    'boiler_manufacturer' => $quote->boiler_manufacturer,
                    'type_number' => $quote->boiler_type,
                    'serial_number' => $quote->boiler_serial_number,
                    'reported_problem' => $quote->message,
                    'work_accepted_at' => now(),
                ]);

                $this->logActionCompleted('works', $work, 'Work created from accepted quote', ['quote_id' => $quote->id]);

                return $work;
            });
        } catch (Throwable $exception) {
            $this->logActionAttempt('quotes', $quote, $exception);

            throw $exception;
        }
    }
}
