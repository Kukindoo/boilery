<?php

namespace App\Livewire\Quotes;

use App\Enums\Permissions;
use App\Models\Quote;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerQuotesTable extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $user = auth()->user();

        abort_unless($user && ($user->can(Permissions::QUOTE_VIEW_OWN->label()) || $user->can('viewAny', Quote::class)), 403);

        $search = mb_trim($this->search);

        $quotes = $user->quotes()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('quotes.id', mb_ltrim($search, '#'))
                        ->orWhere('boiler_type', 'like', '%' . $search . '%')
                        ->orWhere('boiler_serial_number', 'like', '%' . $search . '%')
                        ->orWhere('message', 'like', '%' . $search . '%');
                });
            })
            ->latest('quotes.id')
            ->paginate(10);

        return view('livewire.quotes.customer-quotes-table', ['quotes' => $quotes]);
    }
}
