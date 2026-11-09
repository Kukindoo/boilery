<?php

namespace Tests\Feature;

use App\Actions\SpareParts\ForceDeleteSparePart;
use App\Actions\SpareParts\RestoreSparePart;
use App\Enums\Permissions;
use App\Enums\Roles;
use App\Livewire\Modals\Deletion;
use App\Livewire\SpareParts\SparePartCreate;
use App\Livewire\SpareParts\SparePartEdit;
use App\Livewire\SpareParts\SparePartsTable;
use App\Livewire\SpareParts\WorkSparePartAdd;
use App\Livewire\SpareParts\WorkSparePartRow;
use App\Livewire\SpareParts\WorkSpareParts;
use App\Models\SparePart;
use App\Models\User;
use App\Models\Work;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Spatie\Permission\Models\Role;

class SparePartsCrudTest extends FeatureTestCase
{
    public function test_create_and_edit_persist_fields_and_normalize_empty_values(): void
    {
        $admin = User::factory()->admin()->create();
        Livewire::actingAs($admin)->test(SparePartCreate::class)
            ->set('form.name', 'Heating element')->set('form.partNumber', 'SP-1')
            ->set('form.description', 'Replacement element')->set('form.price', '123,45')->call('submit')->assertHasNoErrors();
        $part = SparePart::query()->sole();
        $this->assertSame('Heating element', $part->name);
        $this->assertSame('SP-1', $part->part_number);
        $this->assertSame(12345, $part->price_in_cents);
        $this->assertSame('Replacement element', $part->description);

        Livewire::test(SparePartEdit::class, ['sparePart' => $part])
            ->assertSet('form.price', '123,45')->set('form.price', '0,00')
            ->set('form.name', 'Thermostat')->set('form.partNumber', '')->set('form.description', '')
            ->call('submit')->assertHasNoErrors()->assertRedirect(route('spare-parts.show', $part));
        $this->assertSame('Thermostat', $part->refresh()->name);
        $this->assertSame(0, $part->price_in_cents);
        $this->assertNull($part->part_number);
        $this->assertNull($part->description);
    }

    #[TestWith(['', null, 'required'])]
    #[TestWith(['Valid', 'long', 'max'])]
    public function test_invalid_input_does_not_create_a_part(string $name, ?string $number, string $rule): void
    {
        Livewire::actingAs(User::factory()->admin()->create())->test(SparePartCreate::class)
            ->set('form.name', $name)->set('form.partNumber', $number === 'long' ? str_repeat('x', 256) : $number)
            ->call('submit')->assertHasErrors([$rule === 'required' ? 'form.name' : 'form.partNumber' => $rule]);
        $this->assertDatabaseCount('spare_parts', 0);
    }

    #[TestWith(['-1'])]
    #[TestWith(['12,345'])]
    #[TestWith([''])]
    #[TestWith(['42949672,96'])]
    public function test_invalid_price_is_rejected_on_create_and_update(string $price): void
    {
        $user = User::factory()->admin()->create();
        $part = SparePart::factory()->create(['price_in_cents' => 1200]);
        Livewire::actingAs($user)->test(SparePartCreate::class)
            ->set('form.name', 'New part')->set('form.price', $price)
            ->call('submit')->assertHasErrors('form.price');
        Livewire::test(SparePartEdit::class, ['sparePart' => $part])
            ->set('form.price', $price)->call('submit')
            ->assertHasErrors('form.price');
        $this->assertSame(1200, $part->fresh()->price_in_cents);
        $this->assertDatabaseCount('spare_parts', 1);
    }

    #[TestWith(['15,00', 1500, 'CZK 15,00'])]
    #[TestWith(['17,45', 1745, 'CZK 17,45'])]
    #[TestWith(['17.45', 1745, 'CZK 17,45'])]
    #[TestWith(['0,01', 1, 'CZK 0,01'])]
    #[TestWith(['42949672,95', 4294967295, 'CZK 42 949 672,95'])]
    public function test_czk_prices_are_stored_in_cents_and_displayed_as_currency(string $price, int $cents, string $formatted): void
    {
        $admin = User::factory()->admin()->create();
        Livewire::actingAs($admin)->test(SparePartCreate::class)
            ->set('form.name', 'Priced part')->set('form.price', $price)
            ->call('submit')->assertHasNoErrors();
        $part = SparePart::query()->sole();
        $this->assertSame($cents, $part->price_in_cents);
        $this->get(route('spare-parts.show', $part))->assertSee($formatted);
        Livewire::test(SparePartsTable::class)->assertSee($formatted);
        $work = Work::factory()->create();
        $work->spareParts()->attach($part);
        Livewire::test(WorkSpareParts::class, ['work' => $work])->assertSee($formatted);
    }

    public function test_permissions_are_provisioned_for_admin_and_stakeholder_only(): void
    {
        foreach ([Roles::ADMIN, Roles::STAKEHOLDER] as $role) {
            $this->assertTrue(Role::findById($role->value)->hasPermissionTo(Permissions::SPARE_PART_CREATE_ALL));
        }
        $this->assertFalse(Role::findById(Roles::CUSTOMER->value)->hasPermissionTo(Permissions::SPARE_PART_VIEW_ALL));
    }

    public function test_revoked_permissions_block_direct_submission_and_delete(): void
    {
        $user = User::factory()->admin()->create();
        $part = SparePart::factory()->create();
        $create = Livewire::actingAs($user)->test(SparePartCreate::class)->set('form.name', 'Forbidden');
        $edit = Livewire::test(SparePartEdit::class, ['sparePart' => $part])->set('form.name', 'Forbidden');
        $table = Livewire::test(Deletion::class, ['model' => $part, 'heading' => 'Smazat?', 'text' => 'Potvrďte odstranění.'])->call('confirmDelete');
        Role::findById(Roles::ADMIN->value)->revokePermissionTo([
            Permissions::SPARE_PART_CREATE_ALL->label(), Permissions::SPARE_PART_EDIT_ALL->label(), Permissions::SPARE_PART_DELETE_ALL->label(),
        ]);
        Livewire::actingAs($user->fresh());
        $create->call('submit')->assertForbidden();
        $edit->call('submit')->assertForbidden();
        $table->call('delete')->assertForbidden();
        $this->assertSame($part->name, $part->fresh()->name);
        $this->assertNotSoftDeleted($part);
        $this->assertDatabaseCount('spare_parts', 1);
    }

    public function test_unused_part_can_be_archived_restored_and_permanently_deleted(): void
    {
        $user = User::factory()->admin()->create();
        $part = SparePart::factory()->create();
        Livewire::actingAs($user)->test(Deletion::class, ['model' => $part, 'heading' => 'Smazat?', 'text' => 'Potvrďte odstranění.'])
            ->call('confirmDelete')->assertSet('show', true)
            ->call('delete')->assertHasNoErrors()
            ->assertSet('show', false);
        $this->assertSoftDeleted($part);
        app(RestoreSparePart::class)->handle($part->refresh());
        $this->assertFalse($part->refresh()->trashed());
        $part->delete();
        app(ForceDeleteSparePart::class)->handle($part);
        $this->assertModelMissing($part);
        $this->assertDatabaseMissing('spare_part_work', ['spare_part_id' => $part->id]);
    }

    public function test_work_links_are_many_to_many_duplicate_safe_and_removable(): void
    {
        $admin = User::factory()->admin()->create();
        $works = Work::factory()->count(2)->create();
        $part = SparePart::factory()->create();
        $other = SparePart::factory()->create();
        $component = Livewire::actingAs($admin)->test(WorkSparePartAdd::class, ['work' => $works[0]]);
        $component->set('form.sparePartId', (string) $part->id)->call('submit')->assertHasNoErrors();
        $component->set('form.sparePartId', (string) $part->id)->call('submit')->assertHasNoErrors();
        $component->set('form.sparePartId', (string) $other->id)->call('submit')->assertHasNoErrors();
        Livewire::test(WorkSparePartAdd::class, ['work' => $works[1]])
            ->set('form.sparePartId', (string) $part->id)->call('submit')->assertHasNoErrors();
        $this->assertSame(2, $works[0]->spareParts()->count());
        $this->assertSame(2, $part->works()->count());
        Livewire::test(WorkSparePartRow::class, ['work' => $works[0], 'part' => $part, 'amount' => 1])->call('delete')->assertHasNoErrors();
        $this->assertDatabaseMissing('spare_part_work', ['spare_part_id' => $part->id, 'work_id' => $works[0]->id]);
        $this->assertSame(1, $part->works()->count());
        $this->assertModelExists($part);
    }

    public function test_add_modal_preserves_invalid_selection_and_dispatches_refresh_only_after_success(): void
    {
        $admin = User::factory()->admin()->create();
        $work = Work::factory()->create();
        $part = SparePart::factory()->create(['name' => 'Modal spare part']);
        Livewire::actingAs($admin)->test(WorkSpareParts::class, ['work' => $work])
            ->assertSeeLivewire(WorkSparePartAdd::class);
        $component = Livewire::test(WorkSparePartAdd::class, ['work' => $work])
            ->call('open')->set('form.sparePartId', '999999')->call('submit')
            ->assertHasErrors('form.sparePartId')->assertSet('form.sparePartId', '999999')
            ->assertNotDispatched('spare-part-added');
        $this->assertDatabaseCount('spare_part_work', 0);

        $component->set('form.sparePartId', (string) $part->id)->call('submit')
            ->assertHasNoErrors()->assertSet('form.sparePartId', null)
            ->assertDispatched('spare-part-added');
        $this->assertDatabaseHas('spare_part_work', ['work_id' => $work->id, 'spare_part_id' => $part->id]);
        Livewire::test(WorkSpareParts::class, ['work' => $work])->assertSee('Modal spare part');
    }

    public function test_add_form_reauthorizes_submission_after_work_permissions_are_revoked(): void
    {
        $admin = User::factory()->admin()->create();
        $work = Work::factory()->create();
        $part = SparePart::factory()->create();
        $component = Livewire::actingAs($admin)->test(WorkSparePartAdd::class, ['work' => $work])
            ->set('form.sparePartId', (string) $part->id);
        Role::findById(Roles::ADMIN->value)->revokePermissionTo([
            Permissions::WORK_EDIT_ALL->label(), Permissions::WORK_EDIT_OWN->label(),
        ]);
        Livewire::actingAs($admin->fresh());

        $component->call('submit')->assertForbidden();
        $this->assertDatabaseCount('spare_part_work', 0);
    }

    public function test_work_amounts_and_totals_are_saved_and_scoped_to_each_work(): void
    {
        $admin = User::factory()->admin()->create();
        $work = Work::factory()->create();
        $otherWork = Work::factory()->create();
        $first = SparePart::factory()->create(['price_in_cents' => 1745]);
        $second = SparePart::factory()->create(['price_in_cents' => 1500]);
        $component = Livewire::actingAs($admin)->test(WorkSparePartAdd::class, ['work' => $work]);
        $component->set('form.sparePartId', (string) $first->id)->set('form.amount', '3')
            ->call('submit')->assertHasNoErrors()->assertSet('form.amount', '1');
        $component->set('form.sparePartId', (string) $second->id)->set('form.amount', '2')
            ->call('submit')->assertHasNoErrors();
        $otherWork->spareParts()->attach($first, ['amount' => 10]);
        $this->assertDatabaseHas('spare_part_work', ['work_id' => $work->id, 'spare_part_id' => $first->id, 'amount' => 3]);
        $this->assertSame(3, (int) $first->works()->findOrFail($work->id)->pivot->amount);

        $table = Livewire::test(WorkSpareParts::class, ['work' => $work])
            ->assertSee('CZK 52,35')->assertSee('CZK 30,00')->assertSee('CZK 82,35')
            ->assertDontSee('CZK 174,50');
        Livewire::test(WorkSparePartRow::class, ['work' => $work, 'part' => $first, 'amount' => 3])->call('delete');
        $table->call('$refresh')->assertSee('CZK 30,00')->assertDontSee('CZK 82,35');
        Livewire::test(WorkSparePartRow::class, ['work' => $work, 'part' => $second, 'amount' => 2])->call('delete');
        $table->call('$refresh')->assertSee('CZK 0,00');
    }

    #[TestWith(['0'])]
    #[TestWith(['-1'])]
    #[TestWith(['1.5'])]
    #[TestWith([''])]
    #[TestWith(['65536'])]
    public function test_invalid_amount_does_not_attach_parts_or_reset_modal_input(string $amount): void
    {
        $work = Work::factory()->create();
        $part = SparePart::factory()->create();
        Livewire::actingAs(User::factory()->admin()->create())->test(WorkSparePartAdd::class, ['work' => $work])
            ->set('form.sparePartId', (string) $part->id)->set('form.amount', $amount)
            ->call('submit')->assertHasErrors('form.amount')->assertSet('form.amount', $amount)
            ->assertNotDispatched('spare-part-added');
        $this->assertDatabaseCount('spare_part_work', 0);
    }

    public function test_work_totals_can_exceed_the_unit_price_storage_limit(): void
    {
        $work = Work::factory()->create();
        $part = SparePart::factory()->create(['price_in_cents' => 4294967295]);
        $work->spareParts()->attach($part, ['amount' => 2]);
        Livewire::actingAs(User::factory()->admin()->create())->test(WorkSpareParts::class, ['work' => $work])
            ->assertSee('CZK 85 899 345,90');
    }

    public function test_work_assignment_rejects_missing_and_deleted_parts(): void
    {
        $part = SparePart::factory()->create();
        $part->delete();
        $work = Work::factory()->create();
        $component = Livewire::actingAs(User::factory()->admin()->create())
            ->test(WorkSparePartAdd::class, ['work' => $work]);
        foreach ([(string) $part->id, '999999', ''] as $id) {
            $component->set('form.sparePartId', $id)->call('submit')->assertHasErrors('form.sparePartId');
        }
        $this->assertDatabaseCount('spare_part_work', 0);
    }

    public function test_work_assignment_requires_work_update_permission_and_blocks_customers(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Roles::STAKEHOLDER->value);
        $work = Work::factory()->create();
        $part = SparePart::factory()->create();
        $role = Role::findById(Roles::STAKEHOLDER->value);
        $role->givePermissionTo(Permissions::WORK_VIEW_ALL);
        $role->revokePermissionTo([Permissions::WORK_EDIT_ALL->label(), Permissions::WORK_EDIT_OWN->label()]);
        $user->unsetRelation('roles');
        Livewire::actingAs($user)->test(WorkSparePartAdd::class, ['work' => $work])->assertForbidden();
        $customer = User::factory()->customer()->create();
        $customerWork = Work::factory()->for($customer)->create();
        Livewire::actingAs($customer)->test(WorkSpareParts::class, ['work' => $customerWork])->assertForbidden();
        $this->assertDatabaseCount('spare_part_work', 0);
    }

    public function test_table_search_sort_pagination_and_escaping(): void
    {
        $admin = User::factory()->admin()->create();
        SparePart::factory()->count(16)->create(['name' => 'Thermostat']);
        $part = SparePart::factory()->create(['name' => '<script>alert(1)</script>', 'part_number' => 'UNIQUE-42']);
        $component = Livewire::actingAs($admin)->test(SparePartsTable::class)->call('gotoPage', 2);
        $component->set('search', 'UNIQUE-42')->assertSee('UNIQUE-42')->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
        $this->assertSame(1, $component->get('paginators.page'));
        $component->set('sortBy', 'invalid')->set('sortDirection', 'invalid')->assertSee('UNIQUE-42');
        $component->call('sort', 'name')->assertHasNoErrors();
        $this->actingAs($admin)->get(route('spare-parts.show', $part))->assertSee('&lt;script&gt;', false);
    }
}
