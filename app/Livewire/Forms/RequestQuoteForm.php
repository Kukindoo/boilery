<?php

namespace App\Livewire\Forms;

use App\Actions\Quotes\CreateCustomer;
use App\Actions\Quotes\CreateQuote;
use App\Enums\BoilerManufacturers;
use App\Models\RequestedQuotes;
use Illuminate\Validation\Rule;
use Livewire\Form;
use Throwable;

class RequestQuoteForm extends Form
{
    public ?string $firstName;

    public ?string $lastName;

    public ?string $address;

    public string $phone = '';

    public string $email = '';

    public string $message = '';

    public ?BoilerManufacturers $boilerManufacturer = null;

    public ?string $boilerType;

    public ?string $boilerSerialNumber;

    public string $boilerUnderWarranty = 'no';

    public $fileLabel;

    public $fileReceipt;

    public $fileWarrantyDocument;

    public function rules(): array
    {
        return [
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:500'],
            'boilerManufacturer' => ['nullable', Rule::enum(BoilerManufacturers::class)],
            'boilerType' => ['nullable', 'string', 'max:255'],
            'boilerSerialNumber' => ['nullable', 'string', 'max:255'],
            'boilerUnderWarranty' => ['in:yes,no'],
            'fileLabel' => ['nullable', 'file', 'mimes:pdf,png,jpeg', 'max:10240'],
            'fileReceipt' => ['nullable', 'file', 'mimes:pdf,png,jpeg', 'max:10240'],
            'fileWarrantyDocument' => ['nullable', 'file', 'mimes:png,jpeg', 'max:10240'],
        ];
    }

    /**
     * @throws Throwable
     */
    public function submit(): RequestedQuotes
    {
        $this->validate();

        $user = app(CreateCustomer::class)->handle($this);

        $quote = app(CreateQuote::class)->handle($this, $user);

        $this->reset();

        return $quote;
    }
}
