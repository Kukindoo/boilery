<?php

namespace App\Livewire\Quotes;

use App\Enums\RequestedQuoteStatus;
use App\Models\Quote;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class QuotesTable extends Component
{
    use WithPagination;

    public $sortBy = 'created_at';

    public $sortDirection = 'desc';

    public $selectedStatuses = [
        RequestedQuoteStatus::NEW,
        RequestedQuoteStatus::CONTACTED,
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
        return Quote::query()
            ->tap(fn ($query) => $this->sortBy ? $query->orderBy($this->sortBy, $this->sortDirection) : $query)
//            ->tap(fn ($query) => $query->whereNot('status', RequestedQuoteStatus::DONE))
            ->tap(fn ($query) => $query->whereIn('status', $this->selectedStatuses))
            ->paginate(15);
    }

    public function changeQuoteStatus(Quote $quote, RequestedQuoteStatus $status): void
    {
        $quote->update([
            'status' => $status,
        ]);
    }

    public function openQuote(Quote $quote): void
    {
        $this->redirect(route('quotes.show', $quote));
    }
}
