<?php

namespace Tests\Feature;

use App\Enums\Permissions;
use App\Enums\Roles;
use App\Livewire\Works\WorkEdit;
use App\Models\Quote;
use App\Models\User;
use App\Models\Work;
use Spatie\Permission\Models\Role;

class WorksPagesTest extends FeatureTestCase
{
    public function test_show_links_to_the_parent_issue_instead_of_the_origin_quote(): void
    {
        $user = User::factory()->admin()->create();
        $quote = Quote::factory()->create();
        $work = Work::factory()->for($quote->issue)->create();

        $this->actingAs($user)->get(route('works.show', $work))
            ->assertSee('Případ #' . $work->issue_id)
            ->assertSee('href="' . route('issues.show', $work->issue) . '"', false)
            ->assertDontSee('href="' . route('quotes.show', $quote) . '"', false);
        $this->get(route('works.edit', $work))
            ->assertSee('form.issueId', false)->assertDontSee('form.quoteId', false);
    }

    public function test_show_does_not_display_related_quote_details(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo([Permissions::WORK_VIEW_ALL, Permissions::QUOTE_VIEW_ALL, Permissions::FILE_VIEW_ALL]);
        $quote = Quote::factory()->create([
            'message' => '<script>alert("quote")</script>',
            'boiler_type' => 'Related boiler type',
            'boiler_serial_number' => 'Related serial number',
        ]);
        $work = Work::factory()->for($quote->issue)->create();

        $this->actingAs($user)->get(route('works.show', $work))
            ->assertSee($work->order_id)
            ->assertDontSee($quote->email)
            ->assertDontSee($quote->message)
            ->assertDontSee('Related boiler type')
            ->assertDontSee('Related serial number')
            ->assertDontSeeLivewire(\App\Livewire\Quotes\QuoteEdit::class);
    }

    public function test_show_without_a_related_quote_still_displays_work_details(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permissions::WORK_VIEW_ALL);
        $work = Work::factory()->create();

        $this->actingAs($user)->get(route('works.show', $work))
            ->assertSee($work->order_id)
            ->assertSee('Nový');
    }

    public function test_show_displays_files_from_quotes_on_the_same_issue(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo([Permissions::WORK_VIEW_ALL, Permissions::QUOTE_VIEW_ALL, Permissions::FILE_VIEW_ALL]);
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
        $work = Work::factory()->for($quote->issue)->create();

        $this->actingAs($user)->get(route('works.show', $work))
            ->assertSeeLivewire(\App\Livewire\Files\FilePreviewCard::class)
            ->assertSee(route('files.preview', ['work', $work, $file]), false)
            ->assertDontSeeLivewire(\App\Livewire\Files\SingleFileUpload::class);
    }

    public function test_show_hides_quote_details_without_quote_view_permission(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permissions::WORK_VIEW_ALL);
        $quote = Quote::factory()->create();
        $work = Work::factory()->for($quote->issue)->create();

        $this->actingAs($user)->get(route('works.show', $work))
            ->assertSee($work->order_id)
            ->assertDontSee($quote->email)
            ->assertDontSee($quote->message);
    }

    public function test_pages_require_authentication_and_authorization(): void
    {
        $work = Work::factory()->create();
        foreach (['works.show', 'works.edit'] as $route) {
            $this->get(route($route, $work))->assertRedirect(route('login'));
        }

        $user = User::factory()->create();
        foreach (['works.show', 'works.edit'] as $route) {
            $this->actingAs($user)->get(route($route, $work))->assertForbidden();
        }
    }

    public function test_admin_grants_allow_all_pages_and_missing_records_return_not_found(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findById(Roles::ADMIN->value));
        $work = Work::factory()->create();

        $this->actingAs($user);
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
        $user->givePermissionTo([
            Permissions::WORK_VIEW_OWN,
            Permissions::WORK_EDIT_OWN,
            Permissions::ASSEMBLY_ACTION_VIEW_ALL,
        ]);
        $own = Work::factory()->forCustomer($user)->create();
        $other = Work::factory()->create();

        $this->actingAs($user)->get(route('works.show', $own))->assertOk()->assertSee($own->order_id);
        $this->get(route('works.edit', $own))->assertSeeLivewire(WorkEdit::class);
        $this->get(route('works.show', $other))->assertForbidden();
        $this->get(route('works.edit', $other))->assertForbidden();
    }

    public function test_work_index_has_no_add_button_and_standalone_creation_page_is_unavailable(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)->get(route('works.index'))
            ->assertDontSee('Přidat zakázku')
            ->assertDontSee('href="' . url('/works/create') . '"', false);
        $this->get('/works/create')->assertNotFound();
    }
}
