<?php

namespace App\Livewire\Quotes;

use App\Actions\Quotes\AcceptQuote;
use App\Enums\QuoteStatus;
use App\Models\Quote;
use DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

class QuotesTable extends Component
{
    use WithPagination;

    public $sortBy = 'created_at';

    public $sortDirection = 'desc';

    public $selectedStatuses = [
        QuoteStatus::NEW,
        QuoteStatus::CONTACTED,
        QuoteStatus::PROCESSING,
    ];

    public function sort($column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    #[Computed]
    public function quotes()
    {
        Gate::authorize('viewAny', Quote::class);

        return Quote::query()
            ->tap(fn ($query) => $this->sortBy ? $query->orderBy($this->sortBy, $this->sortDirection) : $query)
//            ->tap(fn ($query) => $query->whereNot('status', RequestedQuoteStatus::DONE))
            ->tap(fn ($query) => $query->whereIn('status', $this->selectedStatuses))
            ->paginate(15);
    }

    /**
     * @throws Throwable
     */
    public function changeQuoteStatus(Quote $quote, QuoteStatus $status): void
    {
        if ($status === QuoteStatus::ACCEPTED) {
            $this->acceptQuote($quote);

            return;
        }

        abort_unless(in_array($status, [
            QuoteStatus::CONTACTED,
            QuoteStatus::REJECTED,
        ], true), 422);

        DB::transaction(function () use ($quote, $status): void {
            $quote->refreshForUpdate();

            Gate::authorize('update', $quote);

            $previousStatus = $quote->status;

            if ($previousStatus === $status) {
                return;
            }

            $quote->update(['status' => $status]);

            activity('quotes')
                ->performedOn($quote)
                ->withProperties([
                    'from_status' => $previousStatus->value,
                    'to_status' => $status->value,
                ])
                ->log(
                    "Quote status changed from {$previousStatus->value} to {$status->value}"
                );
        });
    }

    /**
     * @throws Throwable
     */
    public function acceptQuote(Quote $quote): void
    {
        if (! auth()->user()->can('accept', $quote)) {
            abort(403);
        }

        $work = app(AcceptQuote::class)->handle($quote);

        $this->redirect(route('works.show', $work));
    }

    public function openQuote(Quote $quote): void
    {
        $this->redirect(route('quotes.show', $quote));
    }
}
