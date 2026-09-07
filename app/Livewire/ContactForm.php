<?php

namespace App\Livewire;

use App\Livewire\Forms\ContactsForm;
use App\Models\RequestedQuotes;
use App\Notifications\QuoteSubmittedAdmin;
use App\Notifications\QuoteSubmittedCustomer;
use Illuminate\Support\Facades\Notification;
use Livewire\Component;
use Livewire\WithFileUploads;

class ContactForm extends Component
{
    use WithFileUploads;

    public ContactsForm $form;

    public bool $submitted = false;

    public function render()
    {
        return view('livewire.contact-form');
    }

    public function submit()
    {
        $this->form->validate();

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
            $path = $this->form->fileLabel->store('files');

            $quote->update([
                'label_file_path' => $path,
            ]);
        }

        if ($this->form->fileReceipt) {
            $path = $this->form->fileReceipt->store('files');

            $quote->update([
                'receipt_file_path' => $path,
            ]);
        }

        if ($this->form->fileWarrantyDocument) {
            $path = $this->form->fileWarrantyDocument->store('files');

            $quote->update([
                'warranty_file_path' => $path,
            ]);
        }

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
