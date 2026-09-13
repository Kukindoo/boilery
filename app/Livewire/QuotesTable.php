<?php

namespace App\Livewire;

use App\Models\RequestedQuotes;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class QuotesTable extends Component
{
    use WithPagination;

    public $sortBy = 'created_at';

    public $sortDirection = 'desc';

    public function sort($column)
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
}
