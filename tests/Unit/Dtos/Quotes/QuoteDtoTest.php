<?php

namespace Tests\Unit\Dtos\Quotes;

use App\Dtos\Quotes\QuoteDto;
use App\Enums\BoilerManufacturers;
use App\Enums\QuoteStatus;
use Tests\Unit\UnitTestCase;

class QuoteDtoTest extends UnitTestCase
{
    public function test_maps_populated_fields_to_model_attributes_without_identity(): void
    {
        $dto = new QuoteDto(
            firstName: 'Jan',
            lastName: 'Novak',
            email: 'jan@example.test',
            phone: '+420123456789',
            message: 'Water does not heat.',
            status: QuoteStatus::CONTACTED,
            underWarranty: true,
            address: 'Prague 1',
            userId: 42,
            boilerManufacturer: BoilerManufacturers::DRAZICE,
            boilerSerialNumber: 'SERIAL-1',
            boilerType: 'OKCE 125',
            id: 99,
        );

        $this->assertSame([
            'first_name' => 'Jan',
            'last_name' => 'Novak',
            'email' => 'jan@example.test',
            'phone' => '+420123456789',
            'message' => 'Water does not heat.',
            'status' => QuoteStatus::CONTACTED,
            'under_warranty' => true,
            'address' => 'Prague 1',
            'user_id' => 42,
            'boiler_manufacturer' => BoilerManufacturers::DRAZICE,
            'boiler_serial_number' => 'SERIAL-1',
            'boiler_type' => 'OKCE 125',
        ], $dto->toArray());
    }

    public function test_defaults_preserve_nullable_fields_and_false_warranty(): void
    {
        $dto = new QuoteDto('Jan', 'Novak', 'jan@example.test', '+420123456789', 'No hot water.');

        $this->assertSame([
            'first_name' => 'Jan',
            'last_name' => 'Novak',
            'email' => 'jan@example.test',
            'phone' => '+420123456789',
            'message' => 'No hot water.',
            'status' => QuoteStatus::NEW,
            'under_warranty' => false,
            'address' => null,
            'user_id' => null,
            'boiler_manufacturer' => null,
            'boiler_serial_number' => null,
            'boiler_type' => null,
        ], $dto->toArray());
    }
}
