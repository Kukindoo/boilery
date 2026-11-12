<?php

namespace App\Livewire\Quotes;

use App\Constants\RateLimiterNames;
use App\Livewire\Forms\QuoteForm;
use App\Notifications\Quotes\QuoteSubmittedAdmin;
use App\Notifications\Quotes\QuoteSubmittedCustomer;
use App\Traits\PublicRateLimiter;
use DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class CustomerQuoteRequestPage extends Component
{
    use PublicRateLimiter, WithFileUploads;

    public QuoteForm $form;

    public bool $submitted = false;

    public function mount(): void
    {
        if (auth()->check()) {
            [$firstName, $lastName] = array_pad(explode(' ', Str::squish(auth()->user()->name), 2), 2, '');
            $this->form->firstName = $firstName;
            $this->form->lastName = $lastName;
            $this->form->email = auth()->user()->email;
            $this->form->phone = auth()->user()->phone;
            $this->form->address = auth()->user()->address;
        }
    }

    public function render(): View
    {
        return view('livewire.forms.customer-quote-request-page');
    }

    /**
     * @throws Throwable
     */
    public function submit(): void
    {
        $this->rateLimiter(RateLimiterNames::PUBLIC_QUOTE_SUBMISSION);

        DB::transaction(function (): void {
            $quote = $this->form->submit();

            activity('quotes')
                ->performedOn($quote)
                ->log('Quote requested');

            Notification::route('mail', $quote->email)
                ->notify(new QuoteSubmittedCustomer($quote));

            Notification::route('mail', config('contacts.admin_email'))
                ->notify(new QuoteSubmittedAdmin($quote)->afterCommit());

            DB::afterCommit(function (): void {
                $this->form->reset();
                $this->submitted = true;
            });
        });
    }
}
