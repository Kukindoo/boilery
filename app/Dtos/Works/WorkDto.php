<?php

namespace App\Dtos\Works;

use App\Enums\WorkStatus;

class WorkDto
{
    public function __construct(
        public int $userId,
        public ?int $quoteId,
        public ?string $orderId,
        public ?string $typeOfProduct,
        public ?string $typeNumber,
        public ?string $serialNumber,
        public ?bool $warrantyAccepted,
        public ?string $repairInWarranty,
        public ?string $dayOfPurchase,
        public ?string $buildingType,
        public ?string $reportedProblem,
        public ?string $workAcceptedAt,
        public ?string $workStartedAt,
        public ?string $workCompletedAt,
        public ?string $workPerformedDescription,
        public ?string $transportFrom,
        public ?string $transportTo,
        public ?int $transportRateInCzk,
        public ?float $transportDistanceInKm,
        public ?string $notes,
        public ?string $mechanicName,
        public WorkStatus $status = WorkStatus::NEW,
    ) {}

    /** @return array{user_id: int, quote_id: ?int, order_id: ?string, status: WorkStatus, type_of_product: ?string, type_number: ?string, serial_number: ?string, warranty_accepted: ?bool, repair_in_warranty: ?string, day_of_purchase: ?string, building_type: ?string, reported_problem: ?string, work_accepted_at: ?string, work_started_at: ?string, work_completed_at: ?string, work_performed_description: ?string, transport_from: ?string, transport_to: ?string, transport_rate_in_czk: ?int, transport_distance_in_km: ?float, notes: ?string, mechanic_name: ?string} */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'quote_id' => $this->quoteId,
            'order_id' => $this->orderId,
            'status' => $this->status,
            'type_of_product' => $this->typeOfProduct,
            'type_number' => $this->typeNumber,
            'serial_number' => $this->serialNumber,
            'warranty_accepted' => $this->warrantyAccepted,
            'repair_in_warranty' => $this->repairInWarranty,
            'day_of_purchase' => $this->dayOfPurchase,
            'building_type' => $this->buildingType,
            'reported_problem' => $this->reportedProblem,
            'work_accepted_at' => $this->workAcceptedAt,
            'work_started_at' => $this->workStartedAt,
            'work_completed_at' => $this->workCompletedAt,
            'work_performed_description' => $this->workPerformedDescription,
            'transport_from' => $this->transportFrom,
            'transport_to' => $this->transportTo,
            'transport_rate_in_czk' => $this->transportRateInCzk,
            'transport_distance_in_km' => $this->transportDistanceInKm,
            'notes' => $this->notes,
            'mechanic_name' => $this->mechanicName,
        ];
    }
}
