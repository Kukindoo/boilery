<?php

namespace App\Dtos\Quotes;

use App\Enums\BoilerManufacturers;
use App\Enums\QuoteStatus;

class QuoteDto
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $phone,
        public string $message,
        public QuoteStatus $status = QuoteStatus::NEW,
        public bool $underWarranty = false,
        public ?string $address = null,
        public ?int $userId = null,
        public ?BoilerManufacturers $boilerManufacturer = null,
        public ?string $boilerSerialNumber = null,
        public ?string $boilerType = null,
        public ?int $id = null,
    ) {}

    /**
     * @return array{first_name: string, last_name: string, email: string, phone: string, message: string, status: QuoteStatus, under_warranty: bool, address: ?string, user_id: ?int, boiler_manufacturer: ?BoilerManufacturers, boiler_serial_number: ?string, boiler_type: ?string}
     */
    public function toArray(): array
    {
        return [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email' => $this->email,
            'phone' => $this->phone,
            'message' => $this->message,
            'status' => $this->status,
            'under_warranty' => $this->underWarranty,
            'address' => $this->address,
            'user_id' => $this->userId,
            'boiler_manufacturer' => $this->boilerManufacturer,
            'boiler_serial_number' => $this->boilerSerialNumber,
            'boiler_type' => $this->boilerType,
        ];
    }
}
