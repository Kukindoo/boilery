<?php

namespace Tests\Feature;

use App\Enums\Roles;
use App\Livewire\SpareParts\SparePartCreate;
use App\Livewire\SpareParts\SparePartEdit;
use App\Livewire\SpareParts\SparePartsTable;
use App\Models\SparePart;
use App\Models\User;
use App\Models\Work;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;

class SparePartsPagesTest extends FeatureTestCase
{
    #[TestWith([11])]
    #[TestWith([21])]
    public function test_admin_and_stakeholder_can_access_all_pages(int $role): void
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        $part = SparePart::factory()->create();
        $this->actingAs($user);

        $this->get(route('spare-parts.index'))->assertOk()->assertSeeLivewire(SparePartsTable::class);
        $this->get(route('spare-parts.create'))->assertOk()->assertSeeLivewire(SparePartCreate::class);
        $this->get(route('spare-parts.show', $part))->assertOk()->assertSee($part->name);
        $this->get(route('spare-parts.edit', $part))->assertOk()->assertSeeLivewire(SparePartEdit::class);
    }

    public function test_guests_redirect_to_login_and_customers_cannot_access_pages_or_components(): void
    {
        $part = SparePart::factory()->create();
        $this->get(route('spare-parts.index'))->assertRedirect(route('login'));
        $customer = User::factory()->customer()->create();
        $this->actingAs($customer);
        foreach (['index', 'create', 'show', 'edit'] as $page) {
            $this->get(route('spare-parts.' . $page, in_array($page, ['show', 'edit']) ? $part : []))->assertForbidden();
        }
        Livewire::test(SparePartsTable::class)->assertForbidden();
        Livewire::test(SparePartCreate::class)->assertForbidden();
        Livewire::test(SparePartEdit::class, ['sparePart' => $part])->assertForbidden();
    }

    public function test_missing_and_archived_parts_are_not_found(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->get(route('spare-parts.show', 999999))->assertNotFound();
        $part = SparePart::factory()->create();
        $part->delete();
        $this->get(route('spare-parts.edit', $part))->assertNotFound();
        $this->get(route('spare-parts.show', $part))->assertNotFound();
    }

    public function test_customers_do_not_see_spare_parts_on_their_work(): void
    {
        $customer = User::factory()->customer()->create();
        \Spatie\Permission\Models\Role::findById(Roles::CUSTOMER->value)->givePermissionTo(\App\Enums\Permissions::WORK_VIEW_OWN);
        $work = Work::factory()->for($customer)->create();
        $part = SparePart::factory()->create(['name' => 'Private spare part']);
        $work->spareParts()->attach($part);

        $this->actingAs($customer)->get(route('works.show', $work))->assertOk()
            ->assertDontSee('Private spare part')->assertDontSeeLivewire(\App\Livewire\SpareParts\WorkSpareParts::class);
    }
}
