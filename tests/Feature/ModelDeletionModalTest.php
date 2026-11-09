<?php

namespace Tests\Feature;

use App\Enums\Permissions;
use App\Livewire\Modals\Deletion;
use App\Models\AssemblyAction;
use App\Models\Quote;
use App\Models\SparePart;
use App\Models\User;
use App\Models\Work;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;

class ModelDeletionModalTest extends FeatureTestCase
{
    #[TestWith([Quote::class])]
    #[TestWith([Work::class])]
    #[TestWith([SparePart::class])]
    #[TestWith([AssemblyAction::class])]
    public function test_modal_deletes_the_target_only_after_confirmation(string $modelClass): void
    {
        $user = User::factory()->admin()->create();
        $user->givePermissionTo(Permissions::QUOTE_DELETE_ALL);
        $this->actingAs($user);
        $model = $modelClass::factory()->create();
        $modal = Livewire::test(Deletion::class, [
            'model' => $model, 'heading' => 'Smazat?', 'text' => 'Potvrďte odstranění.',
        ]);
        $modal->call('delete')->assertNotDispatched('deleted');
        $this->assertNotSoftDeleted($model);

        $modal->call('confirmDelete')->assertSet('show', true);
        $this->assertNotSoftDeleted($model);
        $modal->call('delete')->assertSet('show', false)->assertDispatched('deleted');
        $this->assertSoftDeleted($model);
        $this->assertDatabaseHas('activity_log', [
            'subject_id' => $model->getKey(),
            'subject_type' => $model->getMorphClass(),
            'event' => 'action_completed',
        ]);
    }

    public function test_generic_model_without_a_resource_action_can_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->givePermissionTo(Permissions::USER_DELETE_ALL);
        $this->actingAs($admin);
        $customer = User::factory()->customer()->create();

        Livewire::test(Deletion::class, [
            'model' => $customer, 'heading' => 'Smazat?', 'text' => 'Potvrďte odstranění.',
        ])->call('confirmDelete')->call('delete')->assertDispatched('deleted');

        $this->assertModelMissing($customer);
    }

    public function test_modal_rechecks_usage_after_confirmation(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $action = AssemblyAction::factory()->create();
        $modal = Livewire::test(Deletion::class, [
            'model' => $action, 'heading' => 'Smazat?', 'text' => 'Potvrďte odstranění.',
        ])->call('confirmDelete');
        Work::factory()->create()->assemblyActions()->attach($action);

        $modal->call('delete')->assertForbidden();
        $this->assertNotSoftDeleted($action);
    }

    public function test_used_model_cannot_be_confirmed_or_deleted_directly(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $action = AssemblyAction::factory()->create();
        Work::factory()->create()->assemblyActions()->attach($action);
        $parameters = ['model' => $action, 'heading' => 'Smazat?', 'text' => 'Potvrďte odstranění.'];

        Livewire::test(Deletion::class, $parameters)->call('confirmDelete')->assertForbidden();
        Livewire::test(Deletion::class, $parameters)->set('show', true)->call('delete')->assertForbidden();
        $this->assertNotSoftDeleted($action);
    }
}
