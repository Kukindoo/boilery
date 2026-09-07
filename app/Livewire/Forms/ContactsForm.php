<?php

namespace App\Livewire\Forms;

use App\Enums\BoilerManufacturers;
use Illuminate\Validation\Rule;
use Livewire\Form;

class ContactsForm extends Form
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
            'boilerManufacturer' => ['nullable', 'string', Rule::enum(BoilerManufacturers::class)],
            'boilerType' => ['nullable', 'string', 'max:255'],
            'boilerSerialNumber' => ['nullable', 'string', 'max:255'],
            'boilerUnderWarranty' => ['in:yes,no'],
            'fileLabel' => ['nullable', 'file', 'mimes:pdf,png,jpeg', 'max:10240'],
            'fileReceipt' => ['nullable', 'file', 'mimes:pdf,png,jpeg', 'max:10240'],
            'fileWarrantyDocument' => ['nullable', 'file', 'mimes:pdf,png,jpeg', 'max:10240'],
        ];
    }
}
