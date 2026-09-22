<?php

namespace App\Livewire;

use App\Actions\Quotes\CreateCustomer;
use App\Actions\Quotes\CreateQuote;
use App\Livewire\Forms\ContactsForm;
use App\Notifications\QuoteSubmittedAdmin;
use App\Notifications\QuoteSubmittedCustomer;
use Exception;
use Illuminate\Support\Facades\Notification;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class QuoteRequestForm extends Component
{
    use WithFileUploads;

    public ContactsForm $form;

    public bool $submitted = false;

    public function render()
    {
        return view('livewire.contact-form');
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    public function submit(): void
    {
        $this->form->validate();

        $user = app(CreateCustomer::class)->handle($this->form->email);

        $quote = app(CreateQuote::class)->handle($this->form, $user);

        activity('quotes')
            ->performedOn($quote)
            ->log('Quote requested');

        Notification::route('mail', $quote->email)
            ->notify(new QuoteSubmittedCustomer($quote));

        Notification::route('mail', config('contacts.admin_email'))
            ->notify(new QuoteSubmittedAdmin($quote));

        $this->submitted = true;
        $this->form->reset();
    }
}
