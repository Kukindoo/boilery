<?php

namespace Tests\Feature;

use App\Actions\Works\ForceDeleteWork;
use App\Actions\Works\RestoreWork;
use App\Enums\Permissions;
use App\Livewire\Works\WorkCreate;
use App\Livewire\Works\WorkEdit;
use App\Livewire\Works\WorksTable;
use App\Models\Quote;
use App\Models\User;
use App\Models\Work;
use Livewire\Livewire;

class WorksCrudTest extends FeatureTestCase
{
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
            ->set('form.note', '')
            ->call('submit')->assertHasNoErrors();

        $work = Work::query()->where('order_id', 'WORK-001')->firstOrFail();
        $this->assertSame($user->id, $work->user_id);
        $this->assertSame('12.30', $work->transport_distance_in_km);
        $this->assertSame('2026-01-02', $work->day_of_purchase->format('Y-m-d'));
        $this->assertNull($work->note);
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
            ->set('form.note', 'Same quote')->call('submit')->assertHasNoErrors();

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
            ->set('form.note', 'Repaired')->call('submit')
            ->assertHasNoErrors()->assertRedirect(route('works.show', $work));

        $this->assertSame('Repaired', $work->refresh()->note);
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
        $component->set('form.note', 'Unauthorized')->call('submit')->assertForbidden();

        $this->assertSame($user->id, $work->refresh()->user_id);
        $this->assertNull($work->note);
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
