<?php

namespace Tests\Feature\Models;

use App\Models\Quote;
use App\Models\User;
use App\Models\Work;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Enums\ActivityEvent;
use Spatie\Activitylog\Models\Activity;
use Tests\Feature\FeatureTestCase;

class WorkTest extends FeatureTestCase
{
    public function test_persists_service_details_with_the_configured_casts(): void
    {
        $attributes = [
            'order_id' => 'WORK-2026-001',
            'type_of_product' => 'Boiler',
            'type_number' => 'B-100',
            'serial_number' => 'SN-001',
            'warranty_accepted' => true,
            'repair_in_warranty' => 'Approved',
            'day_of_purchase' => '2025-01-15',
            'building_type' => 'House',
            'reported_problem' => 'No heating',
            'work_accepted_at' => '2026-10-01',
            'work_completed_at' => '2026-10-02',
            'work_performed_description' => 'Replaced pump',
            'transport_from' => 'Prague',
            'transport_to' => 'Brno',
            'transport_rate_in_czk' => 15,
            'transport_distance_in_km' => '12.50',
            'note' => 'Customer notified',
            'mechanic_name' => 'Jan Novak',
        ];
        $work = new Work($attributes);
        $work->user()->associate(User::factory()->create());

        $work->save();
        $work->refresh();

        $this->assertDatabaseHas('works', ['id' => $work->id, ...$attributes]);
        $this->assertTrue($work->warranty_accepted);
        $this->assertSame(15, $work->transport_rate_in_czk);
        $this->assertSame('12.50', $work->transport_distance_in_km);
        $this->assertInstanceOf(Carbon::class, $work->day_of_purchase);
        $this->assertSame('2025-01-15', $work->day_of_purchase->toDateString());
        $this->assertInstanceOf(Carbon::class, $work->work_accepted_at);
        $this->assertSame('2026-10-01', $work->work_accepted_at->toDateString());
        $this->assertInstanceOf(Carbon::class, $work->work_completed_at);
        $this->assertSame('2026-10-02', $work->work_completed_at->toDateString());
    }

    public function test_resolves_the_associated_user_and_quote(): void
    {
        $user = User::factory()->create();
        $quote = Quote::factory()->for($user)->create();

        $work = Work::factory()->for($user)->for($quote)->create()->fresh();

        $this->assertTrue($work->user->is($user));
        $this->assertTrue($work->quote->is($quote));
    }

    public function test_supports_a_standalone_work_with_nullable_service_details(): void
    {
        $work = Work::factory()->create()->fresh();

        $this->assertNull($work->quote);
        $this->assertFalse($work->warranty_accepted);
        $this->assertNull($work->repair_in_warranty);
        $this->assertNull($work->day_of_purchase);
        $this->assertNull($work->work_accepted_at);
        $this->assertNull($work->work_completed_at);
        $this->assertNull($work->transport_rate_in_czk);
        $this->assertNull($work->transport_distance_in_km);
    }

    public function test_logs_the_created_work_attributes(): void
    {
        $work = Work::factory()->create(['order_id' => 'WORK-2026-002']);

        $activity = Activity::forSubject($work)->forEvent(ActivityEvent::Created)->sole();

        $this->assertSame('WORK-2026-002', $activity->attribute_changes['attributes']['order_id']);
    }

    public function test_logs_only_changed_attributes_when_updating_a_work(): void
    {
        $this->freezeTime();
        $work = Work::factory()->create(['note' => 'Original note'])->fresh();

        $work->update(['note' => 'Updated note']);

        $this->assertDatabaseHas('works', ['id' => $work->id, 'note' => 'Updated note']);
        $activity = Activity::forSubject($work)->forEvent(ActivityEvent::Updated)->sole();
        $this->assertSame(
            ['attributes' => ['note' => 'Updated note'], 'old' => ['note' => 'Original note']],
            $activity->attribute_changes->toArray(),
        );
    }

    public function test_logs_deletion_of_a_work(): void
    {
        $work = Work::factory()->create();

        $work->delete();

        $this->assertModelMissing($work);
        $activity = Activity::forSubject($work)->forEvent(ActivityEvent::Deleted)->sole();
        $this->assertSame($work->order_id, $activity->attribute_changes['old']['order_id']);
    }
}
