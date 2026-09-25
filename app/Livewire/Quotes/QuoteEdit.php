<?php

namespace App\Livewire\Quotes;

use App\Livewire\Forms\RequestQuoteForm;
use App\Models\RequestedQuotes;
use Flux\Flux;
use Livewire\Component;

class QuoteEdit extends Component
{
    public RequestedQuotes $quote;

    public RequestQuoteForm $form;

    public function mount(RequestedQuotes $quote): void
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
        $this->form->update();
        Flux::modal('edit-personal-info')->close();
        $this->redirect(route('quotes.show', $this->quote));
    }
}
