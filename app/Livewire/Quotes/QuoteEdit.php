<?php

namespace App\Livewire\Quotes;

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
        abort_unless(auth()->user()->can('update', $this->quote), 403);

        $this->form->update();
        Flux::modal('edit-personal-info')->close();
        $this->redirect(route('quotes.show', $this->quote));
    }
}
