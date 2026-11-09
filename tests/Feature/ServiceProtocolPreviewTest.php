<?php

namespace Tests\Feature;

use App\Enums\Permissions;
use App\Livewire\AssemblyActions\WorkAssemblyActionAdd;
use App\Livewire\AssemblyActions\WorkAssemblyActionRow;
use App\Livewire\Pdf\ServiceProtocolPreview;
use App\Models\AssemblyAction;
use App\Models\SparePart;
use App\Models\User;
use App\Models\Work;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;

class ServiceProtocolPreviewTest extends FeatureTestCase
{
    public function test_preview_refreshes_when_assembly_actions_are_added_repriced_and_removed(): void
    {
        Http::preventStrayRequests();
        $work = Work::factory()->create();
        $action = AssemblyAction::factory()->create(['code' => 'PREVIEW-01', 'name' => 'New protocol assembly action']);
        Livewire::actingAs(User::factory()->admin()->create());
        $preview = Livewire::test(ServiceProtocolPreview::class, ['work' => $work])
            ->assertDontSee('PREVIEW-01');

        Livewire::test(WorkAssemblyActionAdd::class, ['work' => $work])
            ->set('form.assemblyActionId', $action->id)->set('form.price', '17,45')
            ->call('submit')->assertHasNoErrors()->assertDispatched('assembly-action-added');
        $preview->dispatch('assembly-action-added')
            ->assertSee('PREVIEW-01')->assertSee('New protocol assembly action')->assertSee('CZK 17,45');

        $row = Livewire::test(WorkAssemblyActionRow::class, [
            'work' => $work, 'action' => $action, 'priceInCents' => 1745,
        ]);
        $row->call('edit')->set('form.price', '25,50')->call('submit')
            ->assertHasNoErrors()->assertDispatched('assembly-actions-changed');
        $preview->dispatch('assembly-actions-changed')->assertSee('CZK 25,50')->assertDontSee('CZK 17,45');

        $row->call('delete')->assertDispatched('assembly-actions-changed');
        $preview->dispatch('assembly-actions-changed')->assertDontSee('PREVIEW-01')->assertDontSee('CZK 25,50');
        Http::assertNothingSent();
        $this->assertTrue($work->files()->doesntExist());
    }

    #[TestWith(['spare-part-added'])]
    #[TestWith(['spare-parts-changed'])]
    public function test_preview_also_refreshes_when_parts_change(string $event): void
    {
        $work = Work::factory()->create();
        $part = SparePart::factory()->create(['name' => 'Protocol preview part', 'price_in_cents' => 1745]);
        Livewire::actingAs(User::factory()->admin()->create());
        $preview = Livewire::test(ServiceProtocolPreview::class, ['work' => $work])
            ->assertDontSee('Protocol preview part');
        $work->spareParts()->attach($part, ['amount' => 2]);

        $preview->dispatch($event)->assertSee('Protocol preview part')->assertSee('CZK 34,90');
    }

    public function test_preview_requires_work_view_permission_and_rechecks_it_on_events(): void
    {
        $customer = User::factory()->customer()->create();
        $customer->givePermissionTo(Permissions::WORK_VIEW_OWN->label());
        $work = Work::factory()->for($customer)->create();
        $otherWork = Work::factory()->create();

        Livewire::actingAs($customer)->test(ServiceProtocolPreview::class, ['work' => $otherWork])->assertForbidden();
        $preview = Livewire::test(ServiceProtocolPreview::class, ['work' => $work])->assertOk();
        $customer->revokePermissionTo(Permissions::WORK_VIEW_OWN->label());
        $preview->dispatch('assembly-action-added')->assertForbidden();
    }

    public function test_work_page_mounts_the_live_preview(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('works.show', Work::factory()->create()))
            ->assertOk()->assertSeeLivewire(ServiceProtocolPreview::class);
    }
}
