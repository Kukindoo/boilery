<?php

namespace App\Livewire\Forms;

use App\Actions\Quotes\CreateCustomer;
use App\Actions\Quotes\CreateQuote;
use App\Enums\BoilerManufacturers;
use App\Models\Quote;
use Illuminate\Validation\Rule;
use Livewire\Form;
use Throwable;

class RequestQuoteForm extends Form
{
    public string $firstName = '';

    public string $lastName = '';

    public ?string $address = null;

    public string $phone = '';

    public string $email = '';

    public string $message = '';

    public ?BoilerManufacturers $boilerManufacturer = null;

    public ?string $boilerType = null;

    public ?string $boilerSerialNumber = null;

    public string $boilerUnderWarranty = 'no';

    public $fileLabel = null;

    public $fileReceipt = null;

    public $fileWarrantyDocument = null;

    public ?Quote $quote = null;

    public function rules(): array
    {
        $mimes = implode(',', config('app.files.mimes'));
        $maxSize = config('app.files.max_size');

        return [
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:500'],
            'boilerManufacturer' => ['nullable', Rule::enum(BoilerManufacturers::class)],
            'boilerType' => ['nullable', 'string', 'max:255'],
            'boilerSerialNumber' => ['nullable', 'string', 'max:255'],
            'boilerUnderWarranty' => ['in:yes,no'],
            'fileLabel' => ['nullable', 'file', "mimes:$mimes", "max:$maxSize"],
            'fileReceipt' => ['nullable', 'file', "mimes:$mimes", "max:$maxSize"],
            'fileWarrantyDocument' => ['nullable', 'file', "mimes:$mimes", "max:$maxSize"],
        ];
    }

    /**
     * @throws Throwable
     */
    public function submit(): Quote
    {
        $this->validate();

        $user = app(CreateCustomer::class)->handle($this);

        $quote = app(CreateQuote::class)->handle($this, $user);

        $this->reset();

        return $quote;
    }

    public function update(): Quote
    {
        $this->validate();

        $this->quote->update([
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'address' => $this?->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'message' => $this->message,
            'boiler_manufacturer' => $this->boilerManufacturer,
            'boiler_type' => $this->boilerType,
            'boiler_serial_number' => $this->boilerSerialNumber,
            'under_warranty' => $this->boilerUnderWarranty === 'yes',
        ]);

        return $this->quote;
    }

    public function initForm(Quote $quote)
    {
        $this->quote = $quote;

        $this->firstName = $quote->first_name;
        $this->lastName = $quote->last_name;
        $this->address = $quote->address;
        $this->phone = $quote->phone;
        $this->email = $quote->email;
        $this->message = $quote->message;
        $this->boilerManufacturer = $quote->boiler_manufacturer;
        $this->boilerType = $quote->boiler_type;
        $this->boilerSerialNumber = $quote->boiler_serial_number;
        $this->boilerUnderWarranty = $quote->under_warranty ? 'yes' : 'no';
    }
}
