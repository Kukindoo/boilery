<?php

namespace App\Livewire;

use App\Enums\RequestedQuoteStatus;
use App\Models\RequestedQuotes;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class QuotesTable extends Component
{
    use WithPagination;

    public $sortBy = 'created_at';

    public $sortDirection = 'desc';

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
        return RequestedQuotes::query()
            ->tap(fn ($query) => $this->sortBy ? $query->orderBy($this->sortBy, $this->sortDirection) : $query)
            ->paginate(15);
    }

    public function resolveQuote(RequestedQuotes $quote): void
    {
        $quote->update([
            'status' => RequestedQuoteStatus::DONE,
        ]);
    }

    public function contactedQuote(RequestedQuotes $quote): void
    {
        $quote->update([
            'status' => RequestedQuoteStatus::CONTACTED,
        ]);
    }

    public function rejectQuote(RequestedQuotes $quote): void
    {
        $quote->update([
            'status' => RequestedQuoteStatus::REJECTED,
        ]);
    }
}
