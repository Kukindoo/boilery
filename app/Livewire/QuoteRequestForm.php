<?php

namespace App\Livewire;

use App\Actions\Files\UploadFile;
use App\Enums\FileTypes;
use App\Livewire\Forms\ContactsForm;
use App\Models\RequestedQuotes;
use App\Notifications\QuoteSubmittedAdmin;
use App\Notifications\QuoteSubmittedCustomer;
use DB;
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
    public function submit()
    {
        $this->form->validate();

        $quote = DB::transaction(function () {
            $quote = RequestedQuotes::create([
                'first_name' => $this->form->firstName,
                'last_name' => $this->form->lastName,
                'email' => $this->form->email,
                'phone' => $this->form->phone,
                'message' => $this->form->message,
                'address' => $this->form->address ?? null,
                'under_warranty' => $this->form->boilerUnderWarranty === 'yes',
                'boiler_manufacturer' => $this->form->boilerManufacturer,
                'boiler_serial_number' => $this->form->boilerSerialNumber ?? null,
                'boiler_type' => $this->form->boilerType ?? null,
            ]);

            if ($this->form->fileLabel) {
                $file = $this->form->fileLabel;

                app(UploadFile::class)->handle(
                    $file,
                    $quote,
                    FileTypes::BOILER_LABEL);
            }

            if ($this->form->fileReceipt) {
                $file = $this->form->fileReceipt;

                app(UploadFile::class)->handle(
                    $file,
                    $quote,
                    FileTypes::RECEIPT);
            }

            if ($this->form->fileWarrantyDocument) {
                $file = $this->form->fileWarrantyDocument;

                app(UploadFile::class)->handle(
                    $file,
                    $quote,
                    FileTypes::WARRANTY);
            }

            return $quote;
        });

        activity('quotes')
            ->performedOn($quote)
            ->log('Quote requested');

        Notification::route('mail', $quote->email)
            ->notify(new QuoteSubmittedCustomer($quote));

        Notification::route('mail', config('contacts.email'))
            ->notify(new QuoteSubmittedAdmin($quote));

        $this->submitted = true;
        $this->form->reset();
    }
}
