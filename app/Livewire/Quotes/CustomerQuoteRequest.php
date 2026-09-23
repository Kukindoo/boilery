<?php

namespace App\Livewire\Quotes;

use App\Livewire\Forms\RequestQuoteForm;
use App\Notifications\QuoteSubmittedAdmin;
use App\Notifications\QuoteSubmittedCustomer;
use Illuminate\Support\Facades\Notification;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class CustomerQuoteRequest extends Component
{
    use WithFileUploads;

    public RequestQuoteForm $form;

    public bool $submitted = false;

    public function render()
    {
        return view('livewire.customer-quote-request');
    }

    /**
     * @throws Throwable
     */
    public function submit()
    {
        $quote = $this->form->submit();

        $this->submitted = true;

        activity('quotes')
            ->performedOn($quote)
            ->log('Quote requested');

        Notification::route('mail', $quote->email)
            ->notify(new QuoteSubmittedCustomer($quote));

        Notification::route('mail', config('contacts.admin_email'))
            ->notify(new QuoteSubmittedAdmin($quote));
    }
}
