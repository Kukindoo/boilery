<?php

namespace Tests\Feature\Works;

use App\Enums\Permissions;
use App\Livewire\Works\WorksTable;
use App\Models\User;
use App\Models\Work;
use Livewire\Livewire;
use Tests\Feature\FeatureTestCase;

class WorksTableTest extends FeatureTestCase
{
    public function test_displays_work_details_newest_first_and_excludes_deleted_works(): void
    {
        $user = $this->viewer();
        $older = Work::factory()->for($user)->create(['order_id' => 'ORDER-OLD', 'created_at' => '2026-01-01']);
        $newer = Work::factory()->for($user)->create([
            'order_id' => 'ORDER-NEW',
            'type_of_product' => 'Boiler',
            'work_accepted_at' => '2026-01-02',
            'work_completed_at' => '2026-01-03',
            'created_at' => '2026-01-02',
        ]);
        Work::factory()->for($user)->create(['order_id' => 'ORDER-DELETED', 'deleted_at' => now()]);

        Livewire::actingAs($user)->test(WorksTable::class)
            ->assertSeeInOrder([$newer->order_id, $older->order_id])
            ->assertSee($user->name)
            ->assertSee('Boiler')
            ->assertSee('02.01.2026')
            ->assertSee('03.01.2026')
            ->assertDontSee('ORDER-DELETED');
    }

    public function test_paginates_fifteen_works(): void
    {
        $user = $this->viewer();
        Work::factory()->count(16)->for($user)->sequence(fn ($sequence) => [
            'order_id' => sprintf('ORDER-%02d', $sequence->index),
            'created_at' => '2026-01-01',
        ])->create();

        Livewire::actingAs($user)->test(WorksTable::class)
            ->assertSee('ORDER-15')
            ->assertDontSee('ORDER-00')
            ->call('setPage', 2)
            ->assertSee('ORDER-00')
            ->assertDontSee('ORDER-15');
    }

    public function test_sorting_toggles_direction_and_resets_pagination(): void
    {
        $user = $this->viewer();
        Work::factory()->count(16)->for($user)->sequence(fn ($sequence) => [
            'order_id' => sprintf('ORDER-%02d', $sequence->index),
        ])->create();

        Livewire::actingAs($user)->test(WorksTable::class)
            ->call('setPage', 2)
            ->call('sort', 'order_id')
            ->assertSet('sortDirection', 'asc')
            ->assertSeeInOrder(['ORDER-00', 'ORDER-01'])
            ->assertDontSee('ORDER-15')
            ->call('sort', 'order_id')
            ->assertSet('sortDirection', 'desc')
            ->assertSeeInOrder(['ORDER-15', 'ORDER-14'])
            ->assertDontSee('ORDER-00');
    }

    public function test_rejects_unrecognized_sort_columns(): void
    {
        Livewire::actingAs($this->viewer())->test(WorksTable::class)
            ->call('sort', 'user_id')->assertStatus(422);
    }

    public function test_rejects_tampered_sort_columns(): void
    {
        Livewire::actingAs($this->viewer())->test(WorksTable::class)
            ->set('sortBy', 'user_id')->assertStatus(422);
    }

    public function test_rejects_tampered_sort_directions(): void
    {
        Livewire::actingAs($this->viewer())->test(WorksTable::class)
            ->set('sortDirection', 'invalid')->assertStatus(422);
    }

    public function test_opens_an_authorized_work(): void
    {
        $user = $this->viewer();
        $work = Work::factory()->for($user)->create();

        Livewire::actingAs($user)->test(WorksTable::class)
            ->call('openWork', $work->id)->assertRedirect(route('works.show', $work));
    }

    public function test_cannot_list_works_without_permission(): void
    {
        Livewire::actingAs(User::factory()->create())->test(WorksTable::class)->assertForbidden();
    }

    public function test_cannot_open_a_work_after_permission_is_revoked(): void
    {
        $user = $this->viewer();
        $work = Work::factory()->for($user)->create();
        $component = Livewire::actingAs($user)->test(WorksTable::class);
        $user->revokePermissionTo(Permissions::WORK_VIEW_ALL);

        $component->call('openWork', $work->id)->assertForbidden();
    }

    public function test_rechecks_permissions_on_livewire_requests(): void
    {
        $user = $this->viewer();
        $component = Livewire::actingAs($user)->test(WorksTable::class);
        $user->revokePermissionTo(Permissions::WORK_VIEW_ALL);

        $component->call('sort', 'order_id')->assertForbidden();
    }

    public function test_renders_empty_state(): void
    {
        Livewire::actingAs($this->viewer())->test(WorksTable::class)->assertSee('Žádné zakázky.');
    }

    public function test_escapes_work_details(): void
    {
        $user = $this->viewer();
        Work::factory()->for($user)->create(['type_of_product' => '<script>alert(1)</script>']);

        Livewire::actingAs($user)->test(WorksTable::class)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    private function viewer(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permissions::WORK_VIEW_ALL);

        return $user;
    }
}
