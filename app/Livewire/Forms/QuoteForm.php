<?php

namespace App\Livewire\Forms;

use App\Actions\Quotes\CreateQuote;
use App\Actions\Quotes\UpdateQuote;
use App\Actions\Users\CreateCustomer;
use App\Dtos\NamePlate;
use App\Dtos\Quotes\QuoteDto;
use App\Enums\BoilerManufacturers;
use App\Models\Quote;
use Illuminate\Validation\Rule;
use Livewire\Form;
use Throwable;

class QuoteForm extends Form
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

    public function messages(): array
    {
        return [
            'firstName.required' => 'Prosím zadejte své jméno.',
            'lastName.required' => 'Prosím zadejte své příjmení.',
            'phone.required' => 'Prosím zadejte své telefonní číslo.',
            'email.required' => 'Prosím zadejte svou e-mailovou adresu.',
            'message.required' => 'Prosím zadejte zprávu.',
            'email.email' => 'Prosím zdejte platnou e-mailovou adresu.',
            'fileLabel.mimes' => 'Dokument musí být typu: ' . implode(', ', config('app.files.mimes')) . '.',
            'fileLabel.max' => 'Dokument nesmí být větší jak ' . (config('app.files.max_size') / 1024) . ' MB.',
            'fileReceipt.mimes' => 'Dokument musí být typu: ' . implode(', ', config('app.files.mimes')) . '.',
            'fileReceipt.max' => 'Dokument nesmí být větší jak  ' . (config('app.files.max_size') / 1024) . ' MB.',
            'fileWarrantyDocument.mimes' => 'Dokument musí být typu: ' . implode(', ', config('app.files.mimes')) . '.',
            'fileWarrantyDocument.max' => 'Dokument nesmí být větší jak   ' . (config('app.files.max_size') / 1024) . ' MB.',
        ];
    }

    /**
     * @throws Throwable
     */
    public function submit(): Quote
    {
        $this->validate();
        $namePlate = new NamePlate($this->firstName, $this->lastName, $this->email);

        $user = app(CreateCustomer::class)->handle($namePlate);

        $quote = app(CreateQuote::class)->handle($this, $user);

        $this->reset();

        return $quote;
    }

    public function update(): Quote
    {
        $this->validate();

        return app(UpdateQuote::class)->handle($this->quote, new QuoteDto(
            firstName: $this->firstName,
            lastName: $this->lastName,
            email: $this->email,
            phone: $this->phone,
            message: $this->message,
            status: $this->quote->status,
            underWarranty: $this->boilerUnderWarranty === 'yes',
            address: $this->address,
            userId: $this->quote->user_id,
            boilerManufacturer: $this->boilerManufacturer,
            boilerSerialNumber: $this->boilerSerialNumber,
            boilerType: $this->boilerType,
        ));
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
