<?php

namespace App\Livewire\Quotes;

use App\Constants\RateLimiterNames;
use App\Livewire\Forms\RequestQuoteForm;
use App\Notifications\QuoteSubmittedAdmin;
use App\Notifications\QuoteSubmittedCustomer;
use App\Traits\PublicRateLimiter;
use DB;
use Illuminate\Support\Facades\Notification;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class CustomerQuoteRequestPage extends Component
{
    use PublicRateLimiter, WithFileUploads;

    public RequestQuoteForm $form;

    public bool $submitted = false;

    public function render()
    {
        return view('livewire.forms.customer-quote-request-page');
    }

    /**
     * @throws Throwable
     */
    public function submit()
    {
        $this->rateLimiter(RateLimiterNames::PUBLIC_QUOTE_SUBMISSION);

        DB::transaction(function () {
            $quote = $this->form->submit();

            $this->submitted = true;

            activity('quotes')
                ->performedOn($quote)
                ->log('Quote requested');

            Notification::route('mail', $quote->email)
                ->notify(new QuoteSubmittedCustomer($quote));

            Notification::route('mail', config('contacts.admin_email'))
                ->notify(new QuoteSubmittedAdmin($quote));
        });
    }
}
