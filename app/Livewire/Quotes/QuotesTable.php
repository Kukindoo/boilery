<?php

namespace App\Livewire\Quotes;

use App\Actions\Quotes\AcceptQuote;
use App\Enums\QuoteStatus;
use App\Models\Quote;
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

    public function changeQuoteStatus(Quote $quote, QuoteStatus $status): void
    {
        if (! auth()->user()->can('update', $quote)) {
            abort(403);
        }

        $quote->update([
            'status' => $status,
        ]);

        if ($quote->wasChanged('status')) {
            activity('quotes')
                ->performedOn($quote)
                ->log('Quote accepted');
        }
    }

    /**
     * @throws Throwable
     */
    public function acceptQuote(Quote $quote): void
    {
        if (! auth()->user()->can('accept', $quote)) {
            abort(403);
        }

        $quote = app(AcceptQuote::class)->handle($quote);

        $this->redirect(route('works.show', $quote->work));
    }

    public function openQuote(Quote $quote): void
    {
        $this->redirect(route('quotes.show', $quote));
    }
}
