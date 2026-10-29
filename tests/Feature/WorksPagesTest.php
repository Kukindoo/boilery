<?php

namespace Tests\Feature;

use App\Enums\Permissions;
use App\Enums\Roles;
use App\Livewire\Works\WorkCreate;
use App\Livewire\Works\WorkEdit;
use App\Models\Quote;
use App\Models\User;
use App\Models\Work;
use Spatie\Permission\Models\Role;

class WorksPagesTest extends FeatureTestCase
{
    public function test_show_displays_related_quote_details(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo([Permissions::WORK_VIEW_ALL, Permissions::QUOTE_VIEW_ALL]);
        $quote = Quote::factory()->create([
            'message' => '<script>alert("quote")</script>',
            'boiler_type' => 'Related boiler type',
            'boiler_serial_number' => 'Related serial number',
        ]);
        $work = Work::factory()->for($quote)->create();

        $this->actingAs($user)->get(route('works.show', $work))
            ->assertSee($quote->first_name)
            ->assertSee($quote->last_name)
            ->assertSee($quote->email)
            ->assertSee($quote->phone)
            ->assertSee($quote->address)
            ->assertSee($quote->message)
            ->assertDontSee($quote->message, false)
            ->assertSee('Related boiler type')
            ->assertSee('Related serial number')
            ->assertSeeLivewire(\App\Livewire\Quotes\QuoteEdit::class);
    }

    public function test_show_without_a_related_quote_still_displays_work_details(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permissions::WORK_VIEW_ALL);
        $work = Work::factory()->create();

        $this->actingAs($user)->get(route('works.show', $work))
            ->assertSee($work->order_id);
    }

    public function test_show_displays_related_quote_files(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo([Permissions::WORK_VIEW_ALL, Permissions::QUOTE_VIEW_ALL]);
        $quote = Quote::factory()->create();
        $file = $quote->files()->create([
            'original_name' => 'receipt.pdf',
            'file_type' => \App\Enums\FileTypes::RECEIPT,
            'extension' => 'pdf',
            'size' => 16,
            'mime_type' => 'application/pdf',
            'path' => 'quotes/receipt.pdf',
            'label' => 'Receipt',
        ]);
        $work = Work::factory()->for($quote)->create();

        $this->actingAs($user)->get(route('works.show', $work))
            ->assertSeeLivewire(\App\Livewire\Quotes\QuoteDownloadCard::class)
            ->assertSee(route('quotes.files.preview', [$quote, $file]))
            ->assertDontSeeLivewire(\App\Livewire\Quotes\SingleFileUpload::class);
    }

    public function test_show_hides_quote_details_without_quote_view_permission(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permissions::WORK_VIEW_ALL);
        $quote = Quote::factory()->create();
        $work = Work::factory()->for($quote)->create();

        $this->actingAs($user)->get(route('works.show', $work))
            ->assertSee($work->order_id)
            ->assertDontSee($quote->email)
            ->assertDontSee($quote->message);
    }

    public function test_pages_require_authentication_and_authorization(): void
    {
        $work = Work::factory()->create();
        foreach (['works.create', 'works.show', 'works.edit'] as $route) {
            $this->get(route($route, $work))->assertRedirect(route('login'));
        }

        $user = User::factory()->create();
        foreach (['works.create', 'works.show', 'works.edit'] as $route) {
            $this->actingAs($user)->get(route($route, $work))->assertForbidden();
        }
    }

    public function test_admin_grants_allow_all_pages_and_missing_records_return_not_found(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findById(Roles::ADMIN->value));
        $work = Work::factory()->create();

        $this->actingAs($user)->get(route('works.create'))->assertSeeLivewire(WorkCreate::class);
        $this->get(route('works.edit', $work))->assertSeeLivewire(WorkEdit::class);
        $this->get(route('works.show', $work))->assertSee($work->order_id);
        $this->get(route('works.show', 999999))->assertNotFound();
        $this->get(route('works.edit', 999999))->assertNotFound();
    }

    public function test_permission_provisioning_handles_existing_permission_id_drift(): void
    {
        $permission = \Spatie\Permission\Models\Permission::findByName(Permissions::WORK_VIEW_ALL->label(), 'web');
        $permission->update(['name' => 'Legacy work permission']);
        $migration = require database_path('migrations/2026_10_15_192128_grant_work_permissions_to_admin.php');

        $migration->up();

        $admin = Role::findById(Roles::ADMIN->value, 'web');
        $this->assertTrue($admin->hasPermissionTo(Permissions::WORK_VIEW_ALL->label()));
        $this->assertTrue($admin->hasPermissionTo('Legacy work permission'));
    }

    public function test_own_permissions_cannot_read_or_edit_another_users_work(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo([Permissions::WORK_VIEW_OWN, Permissions::WORK_EDIT_OWN]);
        $own = Work::factory()->for($user)->create();
        $other = Work::factory()->create();

        $this->actingAs($user)->get(route('works.show', $own))->assertSee($own->order_id);
        $this->get(route('works.edit', $own))->assertSeeLivewire(WorkEdit::class);
        $this->get(route('works.show', $other))->assertForbidden();
        $this->get(route('works.edit', $other))->assertForbidden();
    }
}
