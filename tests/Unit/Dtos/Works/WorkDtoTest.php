<?php

namespace Tests\Unit\Dtos\Works;

use App\Dtos\Works\WorkDto;
use Tests\Unit\UnitTestCase;

class WorkDtoTest extends UnitTestCase
{
    public function test_maps_populated_fields_to_model_attributes(): void
    {
        $dto = new WorkDto(
            userId: 42,
            quoteId: 7,
            orderId: 'ORDER-1',
            typeOfProduct: 'Boiler',
            typeNumber: 'TYPE-1',
            serialNumber: 'SERIAL-1',
            warrantyAccepted: true,
            repairInWarranty: 'Approved',
            dayOfPurchase: '2026-01-01',
            buildingType: 'House',
            reportedProblem: 'No hot water',
            workAcceptedAt: '2026-10-22',
            workCompletedAt: '2026-10-23',
            workPerformedDescription: 'Replaced heater',
            transportFrom: 'Prague',
            transportTo: 'Brno',
            transportRateInCzk: 15,
            transportDistanceInKm: 12.5,
            note: 'Service note',
            mechanicName: 'Jan Novak',
        );

        $this->assertSame([
            'user_id' => 42,
            'quote_id' => 7,
            'order_id' => 'ORDER-1',
            'type_of_product' => 'Boiler',
            'type_number' => 'TYPE-1',
            'serial_number' => 'SERIAL-1',
            'warranty_accepted' => true,
            'repair_in_warranty' => 'Approved',
            'day_of_purchase' => '2026-01-01',
            'building_type' => 'House',
            'reported_problem' => 'No hot water',
            'work_accepted_at' => '2026-10-22',
            'work_completed_at' => '2026-10-23',
            'work_performed_description' => 'Replaced heater',
            'transport_from' => 'Prague',
            'transport_to' => 'Brno',
            'transport_rate_in_czk' => 15,
            'transport_distance_in_km' => 12.5,
            'note' => 'Service note',
            'mechanic_name' => 'Jan Novak',
        ], $dto->toArray());
    }

    public function test_preserves_null_optional_fields(): void
    {
        $dto = new WorkDto(
            userId: 42,
            quoteId: null,
            orderId: null,
            typeOfProduct: null,
            typeNumber: null,
            serialNumber: null,
            warrantyAccepted: null,
            repairInWarranty: null,
            dayOfPurchase: null,
            buildingType: null,
            reportedProblem: null,
            workAcceptedAt: null,
            workCompletedAt: null,
            workPerformedDescription: null,
            transportFrom: null,
            transportTo: null,
            transportRateInCzk: null,
            transportDistanceInKm: null,
            note: null,
            mechanicName: null,
        );

        $this->assertSame([
            'user_id' => 42,
            'quote_id' => null,
            'order_id' => null,
            'type_of_product' => null,
            'type_number' => null,
            'serial_number' => null,
            'warranty_accepted' => null,
            'repair_in_warranty' => null,
            'day_of_purchase' => null,
            'building_type' => null,
            'reported_problem' => null,
            'work_accepted_at' => null,
            'work_completed_at' => null,
            'work_performed_description' => null,
            'transport_from' => null,
            'transport_to' => null,
            'transport_rate_in_czk' => null,
            'transport_distance_in_km' => null,
            'note' => null,
            'mechanic_name' => null,
        ], $dto->toArray());
    }

    public function test_preserves_zero_and_false_values(): void
    {
        $dto = new WorkDto(
            userId: 42,
            quoteId: null,
            orderId: null,
            typeOfProduct: null,
            typeNumber: null,
            serialNumber: null,
            warrantyAccepted: false,
            repairInWarranty: null,
            dayOfPurchase: null,
            buildingType: null,
            reportedProblem: null,
            workAcceptedAt: null,
            workCompletedAt: null,
            workPerformedDescription: null,
            transportFrom: null,
            transportTo: null,
            transportRateInCzk: 0,
            transportDistanceInKm: 0.0,
            note: null,
            mechanicName: null,
        );

        $this->assertSame([
            'user_id' => 42,
            'quote_id' => null,
            'order_id' => null,
            'type_of_product' => null,
            'type_number' => null,
            'serial_number' => null,
            'warranty_accepted' => false,
            'repair_in_warranty' => null,
            'day_of_purchase' => null,
            'building_type' => null,
            'reported_problem' => null,
            'work_accepted_at' => null,
            'work_completed_at' => null,
            'work_performed_description' => null,
            'transport_from' => null,
            'transport_to' => null,
            'transport_rate_in_czk' => 0,
            'transport_distance_in_km' => 0.0,
            'note' => null,
            'mechanic_name' => null,
        ], $dto->toArray());
    }
}
