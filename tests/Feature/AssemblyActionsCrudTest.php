<?php

namespace Tests\Feature;

use App\Actions\AssemblyActions\AttachAssemblyActionToWork;
use App\Actions\AssemblyActions\CreateAssemblyAction;
use App\Actions\AssemblyActions\DetachAssemblyActionFromWork;
use App\Actions\AssemblyActions\ForceDeleteAssemblyAction;
use App\Actions\AssemblyActions\RestoreAssemblyAction;
use App\Dtos\AssemblyActions\AssemblyActionDto;
use App\Enums\Permissions;
use App\Enums\Roles;
use App\Livewire\AssemblyActions\AssemblyActionCreate;
use App\Livewire\AssemblyActions\AssemblyActionEdit;
use App\Livewire\AssemblyActions\AssemblyActionsTable;
use App\Livewire\AssemblyActions\WorkAssemblyActionAdd;
use App\Livewire\AssemblyActions\WorkAssemblyActionRow;
use App\Livewire\Modals\Deletion;
use App\Models\AssemblyAction;
use App\Models\User;
use App\Models\Work;
use App\ValueObjects\Price;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Spatie\Permission\Models\Role;

class AssemblyActionsCrudTest extends FeatureTestCase
{
    public function test_create_edit_soft_delete_restore_and_force_delete(): void
    {
        $this->staff();
        Livewire::test(AssemblyActionCreate::class)->set('form.code', '  AA-01  ')->set('form.name', '  Kontrola  ')
            ->set('form.description', 'Popis')->call('submit')->assertHasNoErrors();
        $action = AssemblyAction::query()->sole();
        $this->assertSame('Kontrola', $action->name);
        $this->assertSame('AA-01', $action->code);
        Livewire::test(AssemblyActionEdit::class, ['assemblyAction' => $action])
            ->assertSet('form.code', 'AA-01')->set('form.code', 'AA-02')->set('form.name', 'Oprava')->set('form.description', '')->call('submit')->assertRedirect(route('assembly-actions.show', $action));
        $this->assertSame('Oprava', $action->refresh()->name);
        $this->assertSame('AA-02', $action->code);
        $this->assertNull($action->description);
        Livewire::test(Deletion::class, ['model' => $action, 'heading' => 'Smazat?', 'text' => 'Potvrďte odstranění.'])->call('confirmDelete')->call('delete')->assertHasNoErrors();
        $this->assertSoftDeleted($action);
        app(RestoreAssemblyAction::class)->handle($action->refresh());
        $this->assertFalse($action->refresh()->trashed());
        $action->delete();
        app(ForceDeleteAssemblyAction::class)->handle($action);
        $this->assertModelMissing($action);
    }

    #[TestWith(['', 'required'])]
    #[TestWith(['   ', 'required'])]
    #[TestWith([256, 'max'])]
    public function test_name_validation(string|int $value, string $rule): void
    {
        $this->staff();
        $value = is_int($value) ? str_repeat('a', $value) : $value;
        Livewire::test(AssemblyActionCreate::class)->set('form.name', $value)->call('submit')->assertHasErrors(['form.name' => $rule]);
        $this->assertDatabaseCount('assembly_actions', 0);
    }

    public function test_description_length_is_validated(): void
    {
        $this->staff();
        Livewire::test(AssemblyActionCreate::class)->set('form.name', 'Kontrola')
            ->set('form.description', str_repeat('a', 10001))->call('submit')->assertHasErrors(['form.description' => 'max']);
    }

    public function test_attach_is_idempotent_supports_multiple_works_and_detach_preserves_models(): void
    {
        $this->staff();
        $action = AssemblyAction::factory()->create();
        $work = Work::factory()->create();
        $other = Work::factory()->create();
        $component = Livewire::test(WorkAssemblyActionAdd::class, ['work' => $work]);
        $component->set('form.assemblyActionId', $action->id)->set('form.price', '17,45')->call('submit')->assertHasNoErrors();
        $component->set('form.assemblyActionId', $action->id)->set('form.price', '17,45')->call('submit')->assertHasNoErrors();
        Livewire::test(WorkAssemblyActionAdd::class, ['work' => $other])->set('form.assemblyActionId', $action->id)->set('form.price', '17,45')->call('submit')->assertHasNoErrors();
        $this->assertSame(2, $action->works()->count());
        $this->assertSame($action->id, $work->assemblyActions()->sole()->id);
        Livewire::test(WorkAssemblyActionRow::class, ['work' => $work, 'action' => $action])->call('delete')->assertHasNoErrors()->assertDispatched('assembly-actions-changed');
        $this->assertSame(0, $work->assemblyActions()->count());
        $this->assertSame(1, $action->works()->count());
        $this->assertModelExists($action);
        $this->assertModelExists($work);
    }

    public function test_deleted_and_nonexistent_actions_cannot_be_attached(): void
    {
        $this->staff();
        $work = Work::factory()->create();
        $action = AssemblyAction::factory()->create();
        $action->delete();
        $component = Livewire::test(WorkAssemblyActionAdd::class, ['work' => $work]);
        foreach ([$action->id, 999999] as $id) {
            $component->set('form.assemblyActionId', $id)->set('form.price', '17,45')->call('submit')->assertHasErrors(['form.assemblyActionId' => 'exists']);
        }
        $this->assertDatabaseCount('assembly_action_work', 0);
    }

    public function test_write_actions_reauthorize_after_permission_revocation(): void
    {
        $user = $this->staff();
        $action = AssemblyAction::factory()->create();
        $edit = Livewire::test(AssemblyActionEdit::class, ['assemblyAction' => $action]);
        $table = Livewire::test(Deletion::class, ['model' => $action, 'heading' => 'Smazat?', 'text' => 'Potvrďte odstranění.'])->call('confirmDelete');
        $attach = Livewire::test(WorkAssemblyActionAdd::class, ['work' => Work::factory()->create()])->set('form.assemblyActionId', $action->id)->set('form.price', '17,45');
        $user->removeRole(Role::findById(Roles::ADMIN->value));
        $edit->set('form.name', 'Unauthorized')->call('submit')->assertForbidden();
        $table->call('delete')->assertForbidden();
        $attach->call('submit')->assertForbidden();
        $this->assertFalse($action->refresh()->trashed());
        $this->assertNotSame('Unauthorized', $action->name);
        $this->assertDatabaseCount('assembly_action_work', 0);
    }

    public function test_attachment_requires_work_edit_permission(): void
    {
        $user = $this->staff();
        $action = AssemblyAction::factory()->create();
        $work = Work::factory()->create();
        $component = Livewire::test(WorkAssemblyActionAdd::class, ['work' => $work])->set('form.assemblyActionId', $action->id)->set('form.price', '17,45');
        Role::findById(Roles::ADMIN->value)->revokePermissionTo([Permissions::WORK_EDIT_ALL->label(), Permissions::WORK_EDIT_OWN->label()]);
        $user->unsetRelation('roles');
        $component->call('submit')->assertForbidden();
        $this->assertDatabaseCount('assembly_action_work', 0);
    }

    public function test_customer_cannot_mount_management_components(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findById(Roles::CUSTOMER->value));
        $this->actingAs($user);
        Livewire::test(AssemblyActionCreate::class)->assertForbidden();
        Livewire::test(AssemblyActionEdit::class, ['assemblyAction' => AssemblyAction::factory()->create()])->assertForbidden();
        Livewire::test(AssemblyActionsTable::class)->assertForbidden();
        Livewire::test(WorkAssemblyActionAdd::class, ['work' => Work::factory()->forCustomer($user)->create()])->assertForbidden();
    }

    public function test_table_search_sort_pagination_and_sort_injection(): void
    {
        $this->staff();
        AssemblyAction::factory()->count(16)->create(['name' => 'Kontrola', 'description' => null]);
        $target = AssemblyAction::factory()->create(['name' => 'Unikátní úkon']);
        Livewire::test(AssemblyActionsTable::class)
            ->set('search', 'Unikátní')->assertSee($target->name)->assertDontSee('Kontrola')
            ->set('search', '')->call('sort', 'name')->assertSet('sortDirection', 'desc')
            ->call('gotoPage', 2)->assertSee('Kontrola')
            ->set('search', 'Unikátní')->assertSee($target->name);
        Livewire::test(AssemblyActionsTable::class)->set('sortBy', 'name desc; DROP TABLE works')->assertSee('Kontrola');
        Livewire::test(AssemblyActionsTable::class)->set('sortDirection', 'invalid')->assertSee('Kontrola');
    }

    public function test_attachment_modal_resets_selection_and_lists_only_available_actions(): void
    {
        $this->staff();
        $work = Work::factory()->create();
        $attached = AssemblyAction::factory()->create(['name' => 'Attached action']);
        $available = AssemblyAction::factory()->create(['name' => 'Available action']);
        $work->assemblyActions()->attach($attached);
        Livewire::test(WorkAssemblyActionAdd::class, ['work' => $work])
            ->assertSee($available->name)->assertDontSee($attached->name)
            ->set('form.assemblyActionId', $available->id)->set('form.price', '17,45')->call('open')
            ->assertSet('form.assemblyActionId', null)
            ->set('form.assemblyActionId', $available->id)->set('form.price', '17,45')->call('submit')
            ->assertDispatched('assembly-action-added')->assertSet('form.assemblyActionId', null);
        $this->assertSame(2, $work->assemblyActions()->count());
    }

    public function test_direct_pivot_actions_reject_deleted_work_and_unrelated_removal(): void
    {
        $this->staff();
        $work = Work::factory()->create();
        $action = AssemblyAction::factory()->create();
        Livewire::test(WorkAssemblyActionRow::class, ['work' => $work, 'action' => $action])
            ->call('delete')->assertNotFound();
        $component = Livewire::test(WorkAssemblyActionAdd::class, ['work' => $work])
            ->set('form.assemblyActionId', $action->id)->set('form.price', '17,45');
        $work->delete();
        $component->call('submit')->assertForbidden();
        $this->assertDatabaseCount('assembly_action_work', 0);
    }

    public function test_actions_log_crud_and_pivot_changes(): void
    {
        $this->staff();
        $work = Work::factory()->create();
        $action = app(CreateAssemblyAction::class)
            ->handle(new AssemblyActionDto('Kontrola', null, 'AA-01'));
        app(AttachAssemblyActionToWork::class)->handle($work, $action, Price::fromCents(1745));
        app(DetachAssemblyActionFromWork::class)->handle($work, $action);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'assembly_actions', 'event' => 'action_completed',
            'description' => 'Assembly action created', 'subject_id' => $action->id,
        ]);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'assembly_actions', 'event' => 'action_completed',
            'description' => 'Assembly action attached to work', 'subject_id' => $action->id,
        ]);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'assembly_actions', 'event' => 'action_completed',
            'description' => 'Assembly action detached from work', 'subject_id' => $action->id,
        ]);
    }

    #[TestWith(['', 'required'])]
    #[TestWith(['   ', 'required'])]
    #[TestWith([256, 'max'])]
    public function test_code_is_required_and_limited_to_255_characters(string|int $code, string $rule): void
    {
        $this->staff();
        $code = is_int($code) ? str_repeat('a', $code) : $code;
        Livewire::test(AssemblyActionCreate::class)->set('form.name', 'Montáž')
            ->set('form.code', $code)->call('submit')->assertHasErrors(['form.code' => $rule]);
        $this->assertDatabaseCount('assembly_actions', 0);
    }

    public function test_code_can_be_searched_sorted_and_selected_when_adding_to_work(): void
    {
        $this->staff();
        $first = AssemblyAction::factory()->create(['name' => 'First action', 'code' => 'AA-01']);
        $second = AssemblyAction::factory()->create(['name' => 'Second action', 'code' => 'BB-02']);
        Livewire::test(AssemblyActionsTable::class)->set('search', 'BB-02')
            ->assertSee('Second action')->assertDontSee('First action')
            ->set('search', '')->call('sort', 'code')->assertSeeInOrder(['AA-01', 'BB-02'])
            ->call('sort', 'code')->assertSeeInOrder(['BB-02', 'AA-01']);
        Livewire::test(WorkAssemblyActionAdd::class, ['work' => Work::factory()->create()])
            ->assertSee($first->code)->assertSee($second->code);
    }

    private function staff(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findById(Roles::ADMIN->value));
        $this->actingAs($user);

        return $user;
    }
}
