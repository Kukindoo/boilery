<?php

namespace App\Livewire\Forms\Work;

use App\Actions\Works\CreateWork;
use App\Actions\Works\UpdateWork;
use App\Dtos\Works\WorkDto;
use App\Enums\Permissions;
use App\Models\Quote;
use App\Models\Work;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Form;

class WorkForm extends Form
{
    #[Locked]
    public ?Work $work = null;

    #[Locked]
    public ?int $id = null;

    #[Locked]
    public ?string $createdAt = null;

    #[Locked]
    public ?string $updatedAt = null;

    #[Locked]
    public ?string $deletedAt = null;

    public ?int $userId = null;

    public ?int $quoteId = null;

    public string $orderId = '';

    public ?string $typeOfProduct = null;

    public ?string $typeNumber = null;

    public ?string $serialNumber = null;

    public bool $warrantyAccepted = false;

    public ?string $repairInWarranty = null;

    public ?string $dayOfPurchase = null;

    public ?string $buildingType = null;

    public ?string $reportedProblem = null;

    public ?string $workAcceptedAt = null;

    public ?string $workCompletedAt = null;

    public ?string $workPerformedDescription = null;

    public ?string $transportFrom = null;

    public ?string $transportTo = null;

    public ?int $transportRateInCzk = null;

    public ?float $transportDistanceInKm = null;

    public ?string $note = null;

    public ?string $mechanicName = null;

    public function initForm(Work $work): void
    {
        $work->refresh();
        $this->work = $work;
        $this->id = $work->id;
        $this->createdAt = $work->created_at?->toDateTimeString();
        $this->updatedAt = $work->updated_at?->toDateTimeString();
        $this->deletedAt = $work->deleted_at?->toDateTimeString();
        $this->userId = $work->user_id;
        $this->quoteId = $work->quote_id;
        $this->orderId = $work->order_id;
        $this->typeOfProduct = $work->type_of_product;
        $this->typeNumber = $work->type_number;
        $this->serialNumber = $work->serial_number;
        $this->warrantyAccepted = $work->warranty_accepted;
        $this->repairInWarranty = $work->repair_in_warranty;
        $this->dayOfPurchase = $work->day_of_purchase?->format('Y-m-d');
        $this->buildingType = $work->building_type;
        $this->reportedProblem = $work->reported_problem;
        $this->workAcceptedAt = $work->work_accepted_at?->format('Y-m-d');
        $this->workCompletedAt = $work->work_completed_at?->format('Y-m-d');
        $this->workPerformedDescription = $work->work_performed_description;
        $this->transportFrom = $work->transport_from;
        $this->transportTo = $work->transport_to;
        $this->transportRateInCzk = $work->transport_rate_in_czk;
        $this->transportDistanceInKm = $work->transport_distance_in_km;
        $this->note = $work->note;
        $this->mechanicName = $work->mechanic_name;
    }

    public function rules(): array
    {
        return [
            'userId' => ['required', 'integer', Rule::exists('users', 'id')],
            'quoteId' => ['nullable', 'integer', Rule::exists('quotes', 'id')->whereNull('deleted_at'), Rule::unique('works', 'quote_id')->ignore($this->work)],
            'orderId' => ['required', 'string', 'max:255', Rule::unique('works', 'order_id')->ignore($this->work)],
            'typeOfProduct' => ['nullable', 'string', 'max:255'],
            'typeNumber' => ['nullable', 'string', 'max:255'],
            'serialNumber' => ['nullable', 'string', 'max:255'],
            'warrantyAccepted' => ['boolean'],
            'repairInWarranty' => ['nullable', 'string', 'max:255'],
            'dayOfPurchase' => ['nullable', 'date_format:Y-m-d'],
            'buildingType' => ['nullable', 'string', 'max:255'],
            'reportedProblem' => ['nullable', 'string', 'max:255'],
            'workAcceptedAt' => ['nullable', 'date_format:Y-m-d'],
            'workCompletedAt' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:workAcceptedAt'],
            'workPerformedDescription' => ['nullable', 'string', 'max:16000'],
            'transportFrom' => ['nullable', 'string', 'max:255'],
            'transportTo' => ['nullable', 'string', 'max:255'],
            'transportRateInCzk' => ['nullable', 'integer', 'between:-2147483648,2147483647'],
            'transportDistanceInKm' => ['nullable', 'numeric', 'decimal:0,2', 'between:-999999.99,999999.99'],
            'note' => ['nullable', 'string', 'max:16000'],
            'mechanicName' => ['nullable', 'string', 'max:16000'],
        ];
    }

    public function submit(): Work
    {
        Gate::authorize('create', Work::class);
        $this->validate();
        $this->authorizeAssignments(true);

        return app(CreateWork::class)->handle($this->toDto());
    }

    public function update(): Work
    {
        abort_if($this->work === null, 404);
        $work = Work::query()->lockForUpdate()->findOrFail($this->work->id);
        Gate::authorize('update', $work);
        $this->validate();
        $this->authorizeAssignments(false);

        return app(UpdateWork::class)->handle($work, $this->toDto());
    }

    private function authorizeAssignments(bool $creating): void
    {
        $permission = $creating ? Permissions::WORK_CREATE_ALL : Permissions::WORK_EDIT_ALL;
        abort_unless(auth()->user()->can($permission->label())
            || (int) $this->userId === auth()->id(), 403);

        if ($this->quoteId !== null && $this->quoteId !== '') {
            $quote = Quote::query()->findOrFail($this->quoteId);
            Gate::authorize('view', $quote);
        }
    }

    private function toDto(): WorkDto
    {
        return new WorkDto(
            userId: (int) $this->userId,
            quoteId: $this->quoteId,
            orderId: $this->orderId,
            typeOfProduct: ($this->typeOfProduct === '' ? null : $this->typeOfProduct),
            typeNumber: ($this->typeNumber === '' ? null : $this->typeNumber),
            serialNumber: ($this->serialNumber === '' ? null : $this->serialNumber),
            warrantyAccepted: $this->warrantyAccepted,
            repairInWarranty: ($this->repairInWarranty === '' ? null : $this->repairInWarranty),
            dayOfPurchase: ($this->dayOfPurchase === '' ? null : $this->dayOfPurchase),
            buildingType: ($this->buildingType === '' ? null : $this->buildingType),
            reportedProblem: ($this->reportedProblem === '' ? null : $this->reportedProblem),
            workAcceptedAt: ($this->workAcceptedAt === '' ? null : $this->workAcceptedAt),
            workCompletedAt: ($this->workCompletedAt === '' ? null : $this->workCompletedAt),
            workPerformedDescription: ($this->workPerformedDescription === '' ? null : $this->workPerformedDescription),
            transportFrom: ($this->transportFrom === '' ? null : $this->transportFrom),
            transportTo: ($this->transportTo === '' ? null : $this->transportTo),
            transportRateInCzk: $this->transportRateInCzk,
            transportDistanceInKm: $this->transportDistanceInKm,
            note: ($this->note === '' ? null : $this->note),
            mechanicName: ($this->mechanicName === '' ? null : $this->mechanicName),
        );
    }
}
