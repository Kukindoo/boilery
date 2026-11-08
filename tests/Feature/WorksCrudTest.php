<?php

namespace Tests\Feature;

use App\Actions\Works\ForceDeleteWork;
use App\Actions\Works\RestoreWork;
use App\Enums\BoilerManufacturers;
use App\Enums\Permissions;
use App\Livewire\Works\WorkCreate;
use App\Livewire\Works\WorkEdit;
use App\Livewire\Works\WorksTable;
use App\Models\Quote;
use App\Models\User;
use App\Models\Work;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Livewire\Livewire;

class WorksCrudTest extends FeatureTestCase
{
    public function test_edit_date_controls_unlock_in_sequence_after_setting_each_date(): void
    {
        $this->freezeTime();
        $user = User::factory()->admin()->create();
        $work = Work::factory()->for($user)->create([
            'work_accepted_at' => null, 'work_started_at' => null, 'work_completed_at' => null,
        ]);
        $today = now()->format('Y-m-d');
        $component = Livewire::actingAs($user)->test(WorkEdit::class, ['work' => $work]);
        $assertButtonDisabled = function (string $field, bool $disabled) use ($component): void {
            $document = new DOMDocument();
            @$document->loadHTML('<?xml encoding="UTF-8">' . $component->html());
            $button = new DOMXPath($document)->query('//*[@data-testid="set-' . $field . '"]')->item(0);
            $this->assertInstanceOf(DOMElement::class, $button);
            $this->assertSame($disabled, $button->hasAttribute('disabled'));
        };

        $assertButtonDisabled('workAcceptedAt', false);
        $assertButtonDisabled('workStartedAt', true);
        $assertButtonDisabled('workCompletedAt', true);
        $component->assertDontSee('wire:model.live.self="form.workAcceptedAt"', false);

        $component->call('$set', 'form.workAcceptedAt', $today)
            ->assertSet('form.workAcceptedAt', $today)
            ->assertSee('wire:model.live.self="form.workAcceptedAt"', false);
        $assertButtonDisabled('workStartedAt', false);
        $assertButtonDisabled('workCompletedAt', true);

        $component->call('$set', 'form.workStartedAt', $today)
            ->assertSee('wire:model.live.self="form.workStartedAt"', false);
        $assertButtonDisabled('workCompletedAt', false);
        $component->call('$set', 'form.workCompletedAt', $today)
            ->assertSee('wire:model.live.self="form.workCompletedAt"', false)
            ->call('submit')->assertHasNoErrors();

        $this->assertSame($today, $work->fresh()->work_completed_at->format('Y-m-d'));
    }

    public function test_edit_requires_preceding_dates_and_preserves_the_work_on_failure(): void
    {
        $user = User::factory()->admin()->create();
        $work = Work::factory()->for($user)->create([
            'work_accepted_at' => '2026-10-19', 'work_started_at' => '2026-10-20',
            'work_completed_at' => '2026-10-21',
        ]);
        $before = $work->refresh()->getRawOriginal();

        Livewire::actingAs($user)->test(WorkEdit::class, ['work' => $work])
            ->set('form.workAcceptedAt', null)->call('submit')
            ->assertHasErrors(['form.workAcceptedAt' => 'required_with']);
        Livewire::test(WorkEdit::class, ['work' => $work])
            ->set('form.workStartedAt', null)->call('submit')
            ->assertHasErrors(['form.workStartedAt' => 'required_with']);

        $this->assertSame($before, $work->fresh()->getRawOriginal());

        Livewire::test(WorkEdit::class, ['work' => $work])
            ->set('form.workAcceptedAt', null)->set('form.workStartedAt', null)
            ->set('form.workCompletedAt', null)->call('submit')->assertHasNoErrors();
        $this->assertNull($work->fresh()->work_accepted_at);
        $this->assertNull($work->fresh()->work_started_at);
        $this->assertNull($work->fresh()->work_completed_at);
    }

    public function test_create_rejects_later_dates_without_their_predecessors(): void
    {
        $user = User::factory()->admin()->create();

        Livewire::actingAs($user)->test(WorkCreate::class)
            ->set('form.orderId', 'INVALID-DATES')->set('form.workStartedAt', '2026-10-20')
            ->call('submit')->assertHasErrors(['form.workAcceptedAt' => 'required_with']);
        Livewire::test(WorkCreate::class)
            ->set('form.orderId', 'INVALID-DATES')->set('form.workCompletedAt', '2026-10-21')
            ->call('submit')->assertHasErrors([
                'form.workAcceptedAt' => 'required_with', 'form.workStartedAt' => 'required_with',
            ]);

        $this->assertDatabaseMissing('works', ['order_id' => 'INVALID-DATES']);
    }

    public function test_create_keeps_manufacturer_separate_from_product_type(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permissions::WORK_CREATE_OWN);

        Livewire::actingAs($user)->test(WorkCreate::class)
            ->set('form.orderId', 'MANUFACTURER-TEST')
            ->set('form.typeOfProduct', 'Ohřívač vody')
            ->set('form.boilerManufacturer', BoilerManufacturers::DRAZICE->value)
            ->call('submit')->assertHasNoErrors();

        $work = Work::query()->sole();
        $this->assertSame('Ohřívač vody', $work->type_of_product);
        $this->assertSame(BoilerManufacturers::DRAZICE, $work->boiler_manufacturer);
    }

    public function test_manufacturer_can_be_edited_validated_and_cleared(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo([Permissions::WORK_EDIT_OWN, Permissions::WORK_VIEW_OWN]);
        $work = Work::factory()->for($user)->create([
            'type_of_product' => 'Ohřívač vody',
            'boiler_manufacturer' => BoilerManufacturers::DRAZICE,
        ]);

        $component = Livewire::actingAs($user)->test(WorkEdit::class, ['work' => $work])
            ->assertSet('form.boilerManufacturer', 'drazice');
        $component->set('form.boilerManufacturer', 'invalid')->call('submit')
            ->assertHasErrors(['form.boilerManufacturer']);
        $this->assertSame(BoilerManufacturers::DRAZICE, $work->refresh()->boiler_manufacturer);

        $component->set('form.boilerManufacturer', 'ariston')->call('submit')->assertHasNoErrors();
        $this->assertSame(BoilerManufacturers::ARISTON, $work->refresh()->boiler_manufacturer);
        $this->get(route('works.show', $work))->assertOk()->assertSee('Ariston');

        Livewire::test(WorkEdit::class, ['work' => $work])
            ->set('form.boilerManufacturer', '')->call('submit')->assertHasNoErrors();
        $this->assertNull($work->refresh()->boiler_manufacturer);
        $this->assertSame('Ohřívač vody', $work->type_of_product);
    }

    public function test_edit_loads_and_saves_work_status(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo([Permissions::WORK_EDIT_OWN, Permissions::WORK_VIEW_OWN]);
        $work = Work::factory()->for($user)->create(['status' => \App\Enums\WorkStatus::PROCESSING]);

        Livewire::actingAs($user)->test(WorkEdit::class, ['work' => $work])
            ->assertSet('form.status', 'processing')
            ->assertSee('Dokončeno')
            ->set('form.status', 'finished')
            ->call('submit')->assertHasNoErrors()
            ->assertRedirect(route('works.show', $work));

        $this->assertSame(\App\Enums\WorkStatus::FINISHED, $work->refresh()->status);
        $this->get(route('works.show', $work))->assertOk()->assertSee('Dokončeno');
    }

    public function test_edit_loads_saves_displays_and_clears_work_start_date(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo([Permissions::WORK_EDIT_OWN, Permissions::WORK_VIEW_OWN]);
        $work = Work::factory()->for($user)->create(['work_accepted_at' => '2026-10-19', 'work_started_at' => '2026-10-20']);

        $component = Livewire::actingAs($user)->test(WorkEdit::class, ['work' => $work])
            ->assertSet('form.workStartedAt', '2026-10-20')
            ->assertSee('Datum zahájení');
        $component->set('form.workStartedAt', 'invalid')->call('submit')
            ->assertHasErrors(['form.workStartedAt' => 'date_format']);
        $this->assertSame('2026-10-20', $work->refresh()->work_started_at->toDateString());

        $component->set('form.workStartedAt', '2026-10-21')->call('submit')->assertHasNoErrors();
        $this->assertSame('2026-10-21', $work->refresh()->work_started_at->toDateString());
        $this->get(route('works.show', $work))->assertSee('Datum zahájení')->assertSee('21-10-2026');

        Livewire::test(WorkEdit::class, ['work' => $work])
            ->set('form.workStartedAt', '')->call('submit')->assertHasNoErrors();
        $this->assertNull($work->refresh()->work_started_at);
    }

    public function test_edit_rejects_invalid_or_empty_status_without_saving(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permissions::WORK_EDIT_OWN);
        $work = Work::factory()->for($user)->create();
        $component = Livewire::actingAs($user)->test(WorkEdit::class, ['work' => $work]);

        $component->set('form.status', 'invalid')->call('submit')->assertHasErrors(['form.status']);
        $component->set('form.status', '')->call('submit')->assertHasErrors(['form.status' => 'required']);

        $this->assertSame(\App\Enums\WorkStatus::NEW, $work->refresh()->status);
    }

    public function test_creates_a_work_with_normalized_fields_and_relationships(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo([Permissions::WORK_CREATE_OWN, Permissions::QUOTE_VIEW_OWN]);
        $quote = Quote::factory()->for($user)->create();

        Livewire::actingAs($user)->test(WorkCreate::class)
            ->set('form.orderId', 'WORK-001')
            ->set('form.quoteId', $quote->id)
            ->set('form.transportDistanceInKm', '12.30')
            ->set('form.dayOfPurchase', '2026-01-02')
            ->set('form.workAcceptedAt', '2026-10-19')
            ->set('form.workStartedAt', '2026-10-20')
            ->set('form.notes', '')
            ->call('submit')->assertHasNoErrors();

        $work = Work::query()->where('order_id', 'WORK-001')->firstOrFail();
        $this->assertSame($user->id, $work->user_id);
        $this->assertSame('12.30', $work->transport_distance_in_km);
        $this->assertSame('2026-01-02', $work->day_of_purchase->format('Y-m-d'));
        $this->assertSame('2026-10-20', $work->work_started_at->toDateString());
        $this->assertNull($work->notes);
        $this->assertTrue($user->works->contains($work));
        $this->assertTrue($quote->work->is($work));
        $this->assertTrue($work->user->is($user));
        $this->assertTrue($work->quote->is($quote));
    }

    public function test_multiple_works_can_be_created_without_a_quote(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permissions::WORK_CREATE_OWN);

        foreach (['NO-QUOTE-1', 'NO-QUOTE-2'] as $orderId) {
            Livewire::actingAs($user)->test(WorkCreate::class)
                ->set('form.orderId', $orderId)->call('submit')->assertHasNoErrors();
        }

        $this->assertDatabaseCount('works', 2);
        $this->assertSame(2, Work::query()->whereNull('quote_id')->count());
        $this->assertNull(Work::query()->firstOrFail()->quote);
    }

    public function test_quote_cannot_be_assigned_to_another_work_on_create_or_edit(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo([Permissions::WORK_CREATE_OWN, Permissions::WORK_EDIT_OWN, Permissions::QUOTE_VIEW_OWN]);
        $quote = Quote::factory()->for($user)->create();
        $linked = Work::factory()->for($user)->for($quote)->create();
        $unlinked = Work::factory()->for($user)->create();

        Livewire::actingAs($user)->test(WorkCreate::class)
            ->set('form.orderId', 'DUPLICATE-QUOTE')->set('form.quoteId', $quote->id)
            ->call('submit')->assertHasErrors(['form.quoteId' => 'unique']);
        Livewire::actingAs($user)->test(WorkEdit::class, ['work' => $unlinked])
            ->set('form.quoteId', $quote->id)->call('submit')->assertHasErrors(['form.quoteId' => 'unique']);
        Livewire::actingAs($user)->test(WorkEdit::class, ['work' => $linked])
            ->set('form.notes', 'Same quote')->call('submit')->assertHasNoErrors();

        $this->assertDatabaseCount('works', 2);
        $this->assertNull($unlinked->refresh()->quote_id);
        $this->assertSame($quote->id, $linked->refresh()->quote_id);
    }

    public function test_soft_deleted_work_keeps_its_quote_reserved(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo([Permissions::WORK_CREATE_OWN, Permissions::QUOTE_VIEW_OWN]);
        $quote = Quote::factory()->for($user)->create();
        Work::factory()->for($user)->for($quote)->trashed()->create();

        Livewire::actingAs($user)->test(WorkCreate::class)
            ->set('form.orderId', 'RESERVED-QUOTE')->set('form.quoteId', $quote->id)
            ->call('submit')->assertHasErrors(['form.quoteId' => 'unique']);

        $this->assertDatabaseCount('works', 1);
    }

    public function test_database_rejects_duplicate_quote_assignments(): void
    {
        $quote = Quote::factory()->create();
        Work::factory()->for($quote)->create();

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        Work::factory()->for($quote)->create();
    }

    public function test_updates_own_work_without_rejecting_its_unchanged_order_number(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permissions::WORK_EDIT_OWN);
        $work = Work::factory()->for($user)->create();

        Livewire::actingAs($user)->test(WorkEdit::class, ['work' => $work])
            ->assertSet('form.notes', null)
            ->set('form.notes', 'Repaired')->call('submit')
            ->assertHasNoErrors()->assertRedirect(route('works.show', $work));

        $this->assertSame('Repaired', $work->refresh()->notes);
    }

    public function test_invalid_and_duplicate_values_are_not_saved(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permissions::WORK_CREATE_OWN);
        $existing = Work::factory()->create();

        Livewire::actingAs($user)->test(WorkCreate::class)
            ->call('submit')->assertHasErrors(['form.orderId' => 'required'])
            ->set('form.orderId', $existing->order_id)
            ->set('form.transportDistanceInKm', '1.234')
            ->set('form.transportRateInCzk', '2147483648')
            ->set('form.dayOfPurchase', 'invalid')
            ->call('submit')->assertHasErrors(['form.orderId' => 'unique', 'form.transportDistanceInKm', 'form.transportRateInCzk', 'form.dayOfPurchase']);

        $this->assertDatabaseCount('works', 1);
    }

    public function test_own_creator_cannot_assign_another_owner_or_an_inaccessible_quote(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo([Permissions::WORK_CREATE_OWN, Permissions::QUOTE_VIEW_OWN]);
        $other = User::factory()->create();
        $quote = Quote::factory()->for($other)->create();

        Livewire::actingAs($user)->test(WorkCreate::class)
            ->set('form.orderId', 'OWNER-TAMPER')->set('form.userId', $other->id)
            ->call('submit')->assertForbidden();
        Livewire::actingAs($user)->test(WorkCreate::class)
            ->set('form.orderId', 'QUOTE-TAMPER')->set('form.quoteId', $quote->id)
            ->call('submit')->assertForbidden();

        $this->assertDatabaseCount('works', 0);
    }

    public function test_edit_rechecks_permission_and_owner_assignment(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permissions::WORK_EDIT_OWN);
        $work = Work::factory()->for($user)->create();
        $other = User::factory()->create();
        Livewire::actingAs($user)->test(WorkEdit::class, ['work' => $work])
            ->set('form.userId', $other->id)->call('submit')->assertForbidden();

        $component = Livewire::actingAs($user)->test(WorkEdit::class, ['work' => $work]);
        $user->revokePermissionTo(Permissions::WORK_EDIT_OWN);
        $component->set('form.notes', 'Unauthorized')->call('submit')->assertForbidden();

        $this->assertSame($user->id, $work->refresh()->user_id);
        $this->assertNull($work->notes);
    }

    public function test_delete_rechecks_permission_and_soft_deletes_only_after_confirmation(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo([Permissions::WORK_VIEW_ALL, Permissions::WORK_DELETE_ALL]);
        $work = Work::factory()->create();
        $component = Livewire::actingAs($user)->test(WorksTable::class)->call('confirmDelete', $work->id);
        $this->assertNotSoftDeleted($work);
        $user->revokePermissionTo(Permissions::WORK_DELETE_ALL);
        $component->call('delete')->assertForbidden();
        $this->assertNotSoftDeleted($work);
        $user->givePermissionTo(Permissions::WORK_DELETE_ALL);
        Livewire::actingAs($user)->test(WorksTable::class)->call('confirmDelete', $work->id)->call('delete');
        $this->assertSoftDeleted($work);
    }

    public function test_search_resets_pagination_and_finds_customer_or_order(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permissions::WORK_VIEW_ALL);
        Work::factory()->create(['order_id' => 'MATCH-ORDER']);
        Work::factory()->create(['order_id' => 'OTHER-ORDER']);

        Livewire::actingAs($user)->test(WorksTable::class)
            ->call('setPage', 2)->set('search', 'MATCH')
            ->assertSee('MATCH-ORDER')->assertDontSee('OTHER-ORDER');
    }

    public function test_restore_and_permanent_deletion_require_lifecycle_permissions(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo([Permissions::WORK_RESTORE_OWN, Permissions::WORK_FORCE_DELETE_ALL]);
        $work = Work::factory()->for($user)->trashed()->create();
        $this->actingAs($user);

        app(RestoreWork::class)->handle($work);
        $this->assertNotSoftDeleted($work);
        $work->delete();
        app(ForceDeleteWork::class)->handle($work);
        $this->assertModelMissing($work);
    }
}
