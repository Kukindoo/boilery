<?php

namespace App\Livewire\Customers\Tables;

use App\Models\Issue;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class QuotesTable extends Component
{
    use WithPagination;

    #[Locked]
    public Issue $issue;

    public string $heading = 'Poptávky zákazníka';

    public bool $paginate = true;

    public function render(): View
    {
        Gate::authorize('view', $this->issue);

        $query = $this->issue->quotes()->latest('id');

        return view('livewire.customers.tables.quotes-table', [
            'quotes' => $this->paginate
                ? $query->paginate(10, pageName: 'quotes_page')
                : $query->get(),
        ]);
    }
}
