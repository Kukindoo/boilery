<?php

namespace Tests\Feature\Works;

use App\Enums\Permissions;
use App\Livewire\Works\WorksTable;
use App\Models\User;
use Tests\Feature\FeatureTestCase;

class WorksControllerIndexTest extends FeatureTestCase
{
    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('works.index'))->assertRedirect(route('login'));
    }

    public function test_users_without_list_permission_are_forbidden(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permissions::WORK_VIEW_OWN);

        $this->actingAs($user)->get(route('works.index'))->assertForbidden();
    }

    public function test_authorized_users_see_the_works_table(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permissions::WORK_VIEW_ALL);

        $this->actingAs($user)->get(route('works.index'))
            ->assertOk()
            ->assertViewIs('works.index')
            ->assertSeeLivewire(WorksTable::class);
    }
}
