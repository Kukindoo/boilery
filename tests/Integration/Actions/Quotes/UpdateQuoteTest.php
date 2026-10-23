<?php

namespace Tests\Integration\Actions\Quotes;

use App\Actions\Quotes\UpdateQuote;
use App\Dtos\Quotes\QuoteDto;
use App\Enums\BoilerManufacturers;
use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Integration\IntegrationTestCase;

class UpdateQuoteTest extends IntegrationTestCase
{
    public static function deniedUpdates(): array
    {
        return [
            'guest' => ['guest', QuoteStatus::NEW, false],
            'no permission' => ['unprivileged', QuoteStatus::NEW, false],
            'another owner' => ['customer', QuoteStatus::NEW, true],
            'accepted' => ['admin', QuoteStatus::ACCEPTED, false],
            'rejected' => ['admin', QuoteStatus::REJECTED, false],
        ];
    }

    public function test_updates_quote_attributes_and_returns_the_same_record(): void
    {
        $customer = User::factory()->customer()->create();
        $this->actingAs($customer);
        $quote = Quote::factory()->for($customer)->create();
        $data = $this->data($customer->id);

        $updated = app(UpdateQuote::class)->handle($quote, $data);

        $this->assertSame($quote, $updated);
        $this->assertDatabaseHas('quotes', [
            'id' => $quote->id,
            'user_id' => $customer->id,
            'first_name' => 'Jan',
            'last_name' => 'Novak',
            'email' => 'jan@example.test',
            'phone' => '+420123456789',
            'message' => 'Updated problem',
            'status' => 'contacted',
            'under_warranty' => true,
            'address' => null,
            'boiler_manufacturer' => 'drazice',
            'boiler_serial_number' => null,
            'boiler_type' => null,
        ]);
        $this->assertSame(BoilerManufacturers::DRAZICE, $quote->fresh()->boiler_manufacturer);
    }

    #[DataProvider('deniedUpdates')]
    public function test_denies_update_without_changing_attributes(string $actor, QuoteStatus $status, bool $anotherOwner): void
    {
        $user = match ($actor) {
            'admin' => User::factory()->admin()->create(),
            'customer' => User::factory()->customer()->create(),
            default => User::factory()->create(),
        };
        if ($actor !== 'guest') {
            $this->actingAs($user);
        }
        $quote = Quote::factory()->for($anotherOwner ? User::factory()->create() : $user)->create(['status' => $status]);
        $quote->refresh();
        $before = $quote->getRawOriginal();

        try {
            app(UpdateQuote::class)->handle($quote, $this->data($quote->user_id));
            $this->fail('Update should be forbidden.');
        } catch (AuthorizationException) {
            $this->assertSame($before, $quote->fresh()->getRawOriginal());
        }
    }

    public function test_own_editor_cannot_reassign_quote(): void
    {
        $customer = User::factory()->customer()->create();
        $this->actingAs($customer);
        $quote = Quote::factory()->for($customer)->create();
        $quote->refresh();
        $before = $quote->getRawOriginal();

        try {
            app(UpdateQuote::class)->handle($quote, $this->data(User::factory()->create()->id));
            $this->fail('Reassignment should be forbidden.');
        } catch (AuthorizationException) {
            $this->assertSame($before, $quote->fresh()->getRawOriginal());
        }
    }

    public function test_all_editor_can_reassign_quote(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $quote = Quote::factory()->for(User::factory()->create())->create();
        $newOwner = User::factory()->create();

        app(UpdateQuote::class)->handle($quote, $this->data($newOwner->id));

        $this->assertSame($newOwner->id, $quote->fresh()->user_id);
    }

    private function data(int $userId): QuoteDto
    {
        return new QuoteDto(
            firstName: 'Jan', lastName: 'Novak', email: 'jan@example.test',
            phone: '+420123456789', message: 'Updated problem',
            status: QuoteStatus::CONTACTED, underWarranty: true, userId: $userId,
            boilerManufacturer: BoilerManufacturers::DRAZICE,
        );
    }
}
