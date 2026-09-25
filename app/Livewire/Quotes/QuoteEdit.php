<?php

namespace App\Livewire\Quotes;

use App\Enums\Permissions;
use App\Enums\RequestedQuoteStatus;
use App\Livewire\Forms\RequestQuoteForm;
use App\Models\Quote;
use Flux\Flux;
use Livewire\Component;

class QuoteEdit extends Component
{
    public Quote $quote;

    public RequestQuoteForm $form;

    public function mount(Quote $quote): void
    {
        $this->quote = $quote;
        $this->form->initForm($this->quote);
    }

    public function render()
    {
        return view('livewire.quotes.quote-edit');
    }

    public function submit()
    {
        $user = auth()->user();
        abort_unless($user->hasPermissionTo(Permissions::QUOTE_EDIT_ALL) or
            ($user->hasPermissionTo(Permissions::QUOTE_VIEW_OWN)
                and $this->quote->user_id === $user->id
                and ($this->quote->status !== RequestedQuoteStatus::ACCEPTED
                    or $this->quote->status !== RequestedQuoteStatus::REJECTED)), 403);

        $this->form->update();
        Flux::modal('edit-personal-info')->close();
        $this->redirect(route('quotes.show', $this->quote));
    }
}
