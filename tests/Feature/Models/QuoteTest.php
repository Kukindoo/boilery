<?php

namespace Tests\Feature\Models;

use App\Enums\BoilerManufacturers;
use App\Enums\FileTypes;
use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\User;
use Spatie\Activitylog\Enums\ActivityEvent;
use Spatie\Activitylog\Models\Activity;
use Tests\Feature\FeatureTestCase;

class QuoteTest extends FeatureTestCase
{
    public function test_persists_customer_and_boiler_details_with_the_configured_casts(): void
    {
        $attributes = [
            'status' => QuoteStatus::PROCESSING->value,
            'first_name' => 'Jan',
            'last_name' => 'Novak',
            'email' => 'jan@example.com',
            'phone' => '+420123456789',
            'address' => 'Prague',
            'message' => 'No heating',
            'under_warranty' => true,
            'boiler_manufacturer' => BoilerManufacturers::ARISTON->value,
            'boiler_serial_number' => 'SN-001',
            'boiler_type' => 'B-100',
        ];
        $quote = new Quote($attributes);
        $quote->user()->associate(User::factory()->create());

        $quote->save();
        $quote->refresh();

        $this->assertDatabaseHas('quotes', ['id' => $quote->id, ...$attributes]);
        $this->assertSame(QuoteStatus::PROCESSING, $quote->status);
        $this->assertSame(BoilerManufacturers::ARISTON, $quote->boiler_manufacturer);
        $this->assertTrue($quote->under_warranty);
    }

    public function test_resolves_the_associated_user(): void
    {
        $user = User::factory()->create();

        $quote = Quote::factory()->for($user)->create()->fresh();

        $this->assertTrue($quote->user->is($user));
    }

    public function test_returns_only_files_belonging_to_the_quote(): void
    {
        $quote = Quote::factory()->create();
        $otherQuote = Quote::factory()->create();
        $attributes = [
            'original_name' => 'receipt.pdf',
            'file_type' => FileTypes::RECEIPT,
            'extension' => 'pdf',
            'size' => 100,
            'mime_type' => 'application/pdf',
            'path' => 'quotes/receipt.pdf',
            'label' => 'Receipt',
        ];
        $file = $quote->files()->create($attributes);
        $otherQuote->files()->create($attributes);

        $this->assertSame([$file->id], $quote->fresh()->files->modelKeys());
    }

    public function test_supports_nullable_customer_and_boiler_details(): void
    {
        $quote = Quote::factory()->create([
            'user_id' => null,
            'address' => null,
            'under_warranty' => false,
            'boiler_manufacturer' => null,
            'boiler_serial_number' => null,
            'boiler_type' => null,
        ])->fresh();

        $this->assertNull($quote->user);
        $this->assertNull($quote->address);
        $this->assertFalse($quote->under_warranty);
        $this->assertNull($quote->boiler_manufacturer);
        $this->assertNull($quote->boiler_serial_number);
        $this->assertNull($quote->boiler_type);
    }

    public function test_processing_quote_is_not_closed(): void
    {
        $quote = Quote::factory()->make(['status' => QuoteStatus::PROCESSING]);

        $this->assertFalse($quote->isClosed());
    }

    public function test_logs_the_created_quote_attributes(): void
    {
        $quote = Quote::factory()->create(['message' => 'No heating']);

        $activity = Activity::forSubject($quote)->forEvent(ActivityEvent::Created)->sole();

        $this->assertSame('No heating', $activity->attribute_changes['attributes']['message']);
    }

    public function test_logs_only_changed_attributes_when_updating_a_quote(): void
    {
        $this->freezeTime();
        $quote = Quote::factory()->create(['message' => 'Original problem'])->fresh();

        $quote->update(['message' => 'Updated problem']);

        $this->assertDatabaseHas('quotes', ['id' => $quote->id, 'message' => 'Updated problem']);
        $activity = Activity::forSubject($quote)->forEvent(ActivityEvent::Updated)->sole();
        $this->assertSame(
            ['attributes' => ['message' => 'Updated problem'], 'old' => ['message' => 'Original problem']],
            $activity->attribute_changes->toArray(),
        );
    }

    public function test_logs_deletion_of_a_quote(): void
    {
        $quote = Quote::factory()->create(['message' => 'No heating']);

        $quote->delete();

        $this->assertModelMissing($quote);
        $activity = Activity::forSubject($quote)->forEvent(ActivityEvent::Deleted)->sole();
        $this->assertSame('No heating', $activity->attribute_changes['old']['message']);
    }
}
