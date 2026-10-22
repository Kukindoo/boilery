<?php

namespace Tests\Integration\Actions\Works;

use App\Actions\Works\CreateWork;
use App\Actions\Works\GenerateOrderNumber;
use App\Dtos\Works\WorkDto;
use App\Enums\BoilerManufacturers;
use App\Models\User;
use App\Models\Work;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Integration\IntegrationTestCase;

class GenerateOrderNumberTest extends IntegrationTestCase
{
    public static function manufacturers(): array
    {
        return [
            'unknown' => [BoilerManufacturers::UNKNOWN, '120261022N'],
            'ariston' => [BoilerManufacturers::ARISTON, '120261022A'],
            'drazice' => [BoilerManufacturers::DRAZICE, '120261022D'],
            'stiebel' => [BoilerManufacturers::STIEBEL, '120261022S'],
        ];
    }

    #[DataProvider('manufacturers')]
    public function test_first_number_uses_current_date_and_manufacturer(BoilerManufacturers $manufacturer, string $expected): void
    {
        $this->travelTo(now()->setDate(2026, 10, 22)->startOfDay());

        $this->assertSame($expected, app(GenerateOrderNumber::class)->handle($manufacturer));
    }

    public function test_sequence_spans_manufacturers_and_includes_archived_works(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 22)->startOfDay());
        Work::factory()->create(['order_id' => '920261022A']);
        Work::factory()->create(['order_id' => '1020261022D'])->delete();
        Work::factory()->create(['order_id' => 'QUOTE-123']);

        $this->assertSame('1120261022S', app(GenerateOrderNumber::class)->handle(BoilerManufacturers::STIEBEL));
    }

    public function test_sequence_restarts_on_next_day(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 23)->startOfDay());
        Work::factory()->create(['order_id' => '1020261022D']);

        $this->assertSame('120261023D', app(GenerateOrderNumber::class)->handle(BoilerManufacturers::DRAZICE));
    }

    public function test_create_work_generates_and_persists_sequential_order_numbers(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 22)->startOfDay());
        $data = new WorkDto(
            userId: User::factory()->create()->id,
            quoteId: null,
            orderId: null,
            typeOfProduct: BoilerManufacturers::DRAZICE->value,
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
            transportRateInCzk: null,
            transportDistanceInKm: null,
            note: null,
            mechanicName: null,
        );
        $action = app(CreateWork::class);

        $first = $action->handle($data);
        $second = $action->handle($data);

        $this->assertSame('120261022D', $first->fresh()->order_id);
        $this->assertSame('220261022D', $second->fresh()->order_id);

        $data->orderId = 'CUSTOM-123';
        $this->assertSame('CUSTOM-123', $action->handle($data)->fresh()->order_id);

        $data->orderId = '';
        $data->typeOfProduct = null;
        $this->assertSame('320261022N', $action->handle($data)->fresh()->order_id);
    }
}
