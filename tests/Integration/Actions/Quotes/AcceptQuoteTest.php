<?php

namespace Tests\Integration\Actions\Quotes;

use App\Actions\Quotes\AcceptQuote;
use App\Enums\BoilerManufacturers;
use App\Enums\Permissions;
use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\User;
use App\Models\Work;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\Integration\IntegrationTestCase;

class AcceptQuoteTest extends IntegrationTestCase
{
    public static function usersWithoutEditAllPermission(): array
    {
        return [
            'guest' => [false],
            'user with only edit-own permission' => [true],
        ];
    }

    public function test_logs_acceptance_and_work_creation_once_with_admin_as_causer(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        $quote = Quote::factory()->for(User::factory()->customer())->create();
        $action = app(AcceptQuote::class);

        $action->handle($quote);
        $action->handle($quote);

        $acceptance = Activity::inLog('quotes')->where('description', 'Quote accepted')->sole();
        $this->assertTrue($acceptance->subject->is($quote));
        $this->assertTrue($acceptance->causer->is($admin));

        $creation = Activity::inLog('works')->where('description', 'Work created from accepted quote')->sole();
        $this->assertTrue($creation->subject->is($quote->work()->sole()));
        $this->assertTrue($creation->causer->is($admin));
        $this->assertSame($quote->id, $creation->getProperty('quote_id'));
    }

    public function test_user_with_edit_all_permission_accepts_quote_and_creates_work_for_its_customer(): void
    {
        $this->freezeTime();
        $role = Role::create(['name' => 'Quote acceptor', 'guard_name' => 'web']);
        $role->givePermissionTo(Permissions::QUOTE_EDIT_ALL->label());
        $user = User::factory()->create();
        $user->assignRole($role);
        $this->actingAs($user);
        $quote = Quote::factory()->for(User::factory()->customer())->create([
            'boiler_manufacturer' => BoilerManufacturers::DRAZICE,
            'boiler_type' => 'OKCE 125',
            'boiler_serial_number' => 'SERIAL-123',
            'message' => 'Water does not heat up.',
            'under_warranty' => true,
        ]);

        $acceptedQuote = app(AcceptQuote::class)->handle($quote);

        $this->assertSame($quote->id, $acceptedQuote->id);
        $this->assertSame(QuoteStatus::ACCEPTED, $quote->fresh()->status);
        $this->assertDatabaseHas('works', [
            'quote_id' => $quote->id,
            'user_id' => $quote->user_id,
            'order_id' => 'QUOTE-' . $quote->id,
            'type_of_product' => 'drazice',
            'type_number' => 'OKCE 125',
            'serial_number' => 'SERIAL-123',
            'reported_problem' => 'Water does not heat up.',
            'warranty_accepted' => false,
            'work_accepted_at' => now()->toDateString(),
        ]);
        $this->assertDatabaseCount('works', 1);
    }

    #[DataProvider('usersWithoutEditAllPermission')]
    public function test_forbids_acceptance_without_edit_all_permission(bool $authenticated): void
    {
        if ($authenticated) {
            $this->actingAs(User::factory()->customer()->create());
        }
        $quote = Quote::factory()->for(User::factory()->customer())->create();

        try {
            app(AcceptQuote::class)->handle($quote);
            $this->fail('Acceptance should be forbidden.');
        } catch (AuthorizationException) {
            $this->assertSame(QuoteStatus::NEW, $quote->fresh()->status);
            $this->assertDatabaseCount('works', 0);
        }
    }

    public function test_repeated_acceptance_preserves_existing_work_even_when_archived(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $quote = Quote::factory()->for(User::factory()->customer())->create();
        $action = app(AcceptQuote::class);
        $action->handle($quote);
        $work = $quote->work()->firstOrFail();
        $work->update(['note' => 'Keep mechanic notes.']);
        $work->delete();

        $action->handle($quote);

        $this->assertDatabaseCount('works', 1);
        $this->assertDatabaseHas('works', ['id' => $work->id, 'note' => 'Keep mechanic notes.']);
        $this->assertSoftDeleted($work);
        $this->assertSame(QuoteStatus::ACCEPTED, $quote->fresh()->status);
    }

    public function test_quote_without_customer_is_not_accepted(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $quote = Quote::factory()->create();

        try {
            app(AcceptQuote::class)->handle($quote);
            $this->fail('A customer should be required.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('quote', $exception->errors());
            $this->assertSame(QuoteStatus::NEW, $quote->fresh()->status);
            $this->assertDatabaseCount('works', 0);
        }
    }

    public function test_work_creation_failure_rolls_back_quote_acceptance(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $quote = Quote::factory()->for(User::factory()->customer())->create();
        Work::factory()->create(['order_id' => 'QUOTE-' . $quote->id]);

        try {
            app(AcceptQuote::class)->handle($quote);
            $this->fail('The conflicting order ID should prevent work creation.');
        } catch (QueryException) {
            $this->assertDatabaseMissing('activity_log', ['description' => 'Quote accepted']);
            $this->assertDatabaseMissing('activity_log', ['description' => 'Work created from accepted quote']);
            $this->assertSame(QuoteStatus::NEW, $quote->fresh()->status);
            $this->assertDatabaseMissing('works', ['quote_id' => $quote->id]);
            $this->assertDatabaseCount('works', 1);
        }
    }

    public function test_admin_cannot_accept_archived_quote(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $quote = Quote::factory()->for(User::factory()->customer())->create();
        $quote->delete();

        $this->expectException(AuthorizationException::class);

        app(AcceptQuote::class)->handle($quote);
    }
}
