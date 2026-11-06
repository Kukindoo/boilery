<?php

namespace Tests\Feature;

use App\Enums\FileTypes;
use App\Enums\Permissions;
use App\Enums\Roles;
use App\Livewire\Quotes\QuoteDownloadCard;
use App\Models\File;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;

class QuoteDownloadCardTest extends FeatureTestCase
{
    /** @return array<string, array{Roles}> */
    public static function editorRoles(): array
    {
        return [
            'admin' => [Roles::ADMIN],
            'stakeholder' => [Roles::STAKEHOLDER],
        ];
    }

    public static function revokedPermissions(): array
    {
        return [
            'download quote access' => ['downloadFile', Permissions::QUOTE_VIEW_OWN],
            'download file access' => ['downloadFile', Permissions::FILE_VIEW_OWN],
            'change quote access' => ['changeFileType', Permissions::QUOTE_VIEW_OWN],
            'change quote edit' => ['changeFileType', Permissions::QUOTE_EDIT_OWN],
            'change file edit' => ['changeFileType', Permissions::FILE_EDIT_OWN],
            'delete quote access' => ['deleteFile', Permissions::QUOTE_VIEW_OWN],
            'delete file permission' => ['deleteFile', Permissions::FILE_DELETE_OWN],
        ];
    }

    public static function actions(): array
    {
        return ['download' => ['downloadFile'], 'change type' => ['changeFileType'], 'delete' => ['deleteFile']];
    }

    #[DataProvider('editorRoles')]
    public function test_role_can_change_file_type(Roles $role): void
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        $quote = Quote::factory()->for(User::factory())->create();
        $file = $quote->files()->create([
            'original_name' => 'receipt.jpg',
            'file_type' => FileTypes::UNKNOWN,
            'extension' => 'jpg',
            'size' => 16,
            'mime_type' => 'image/jpeg',
            'path' => 'quotes/receipt.jpg',
            'label' => 'Receipt',
        ]);

        Livewire::actingAs($user)
            ->test(QuoteDownloadCard::class, ['quote' => $quote, 'file' => $file])
            ->call('changeFileType', FileTypes::RECEIPT->value)
            ->assertHasNoErrors();

        $this->assertSame(FileTypes::RECEIPT, $file->refresh()->file_type);
    }

    public function test_mount_requires_quote_view_permission(): void
    {
        Storage::fake();
        $quote = Quote::factory()->for(User::factory())->create();
        $file = $this->attachment($quote);

        Livewire::actingAs(User::factory()->create())
            ->test(QuoteDownloadCard::class, compact('quote', 'file'))->assertForbidden();
    }

    public function test_mount_rejects_a_file_from_another_quote(): void
    {
        Storage::fake();
        $viewer = User::factory()->create();
        $viewer->givePermissionTo(Permissions::QUOTE_VIEW_ALL);
        $quote = Quote::factory()->create();
        $file = $this->attachment(Quote::factory()->create());

        Livewire::actingAs($viewer)->test(QuoteDownloadCard::class, compact('quote', 'file'))->assertNotFound();
    }

    public function test_authorized_customer_can_download_and_delete_the_card_file(): void
    {
        Storage::fake();
        $viewer = $this->viewer();
        $quote = Quote::factory()->for($viewer)->create();
        $file = $this->attachment($quote);
        $otherFile = $this->attachment($quote, 'other.pdf');

        Livewire::actingAs($viewer)->test(QuoteDownloadCard::class, compact('quote', 'file'))
            ->call('downloadFile')->assertFileDownloaded('receipt.pdf')
            ->call('deleteFile')->assertRedirect(route('quotes.show', $quote));

        $this->assertModelMissing($file);
        Storage::disk()->assertMissing($file->path);
        $this->assertModelExists($otherFile);
        Storage::disk()->assertExists($otherFile->path);
    }

    #[DataProvider('revokedPermissions')]
    public function test_actions_recheck_permissions_after_mount(string $action, Permissions $permission): void
    {
        Storage::fake();
        $viewer = $this->viewer();
        $quote = Quote::factory()->for($viewer)->create();
        $file = $this->attachment($quote);
        $component = Livewire::actingAs($viewer)->test(QuoteDownloadCard::class, compact('quote', 'file'));
        $viewer->revokePermissionTo($permission);

        $component->call($action, ...($action === 'changeFileType' ? [FileTypes::WARRANTY->value] : []))->assertForbidden();

        $this->assertModelExists($file);
        $this->assertSame(FileTypes::RECEIPT, $file->fresh()->file_type);
        Storage::disk()->assertExists($file->path);
    }

    #[DataProvider('actions')]
    public function test_actions_reject_files_moved_to_another_quote_after_mount(string $action): void
    {
        Storage::fake();
        $viewer = $this->viewer();
        $quote = Quote::factory()->for($viewer)->create();
        $file = $this->attachment($quote);
        $component = Livewire::actingAs($viewer)->test(QuoteDownloadCard::class, compact('quote', 'file'));
        $otherQuote = Quote::factory()->for($viewer)->create();
        $file->update(['quote_id' => $otherQuote->id]);

        $component->call($action, ...($action === 'changeFileType' ? [FileTypes::WARRANTY->value] : []))->assertNotFound();

        $this->assertModelExists($file);
        $this->assertSame($otherQuote->id, $file->fresh()->quote_id);
        $this->assertSame(FileTypes::RECEIPT, $file->fresh()->file_type);
        Storage::disk()->assertExists($file->path);
    }

    #[DataProvider('actions')]
    public function test_actions_recheck_quote_ownership_after_mount(string $action): void
    {
        Storage::fake();
        $viewer = $this->viewer();
        $quote = Quote::factory()->for($viewer)->create();
        $file = $this->attachment($quote);
        $component = Livewire::actingAs($viewer)->test(QuoteDownloadCard::class, compact('quote', 'file'));
        Quote::whereKey($quote->id)->update(['user_id' => User::factory()->create()->id]);

        $component->call($action, ...($action === 'changeFileType' ? [FileTypes::WARRANTY->value] : []))->assertForbidden();

        $this->assertModelExists($file);
        $this->assertSame(FileTypes::RECEIPT, $file->fresh()->file_type);
        Storage::disk()->assertExists($file->path);
    }

    public function test_download_rejects_a_quote_archived_after_mount(): void
    {
        Storage::fake();
        $viewer = $this->viewer();
        $quote = Quote::factory()->for($viewer)->create();
        $file = $this->attachment($quote);
        $component = Livewire::actingAs($viewer)->test(QuoteDownloadCard::class, compact('quote', 'file'));
        $quote->delete();

        $component->call('downloadFile')->assertNotFound();
    }

    public function test_file_type_change_rejects_a_quote_closed_after_mount(): void
    {
        Storage::fake();
        $viewer = $this->viewer();
        $quote = Quote::factory()->for($viewer)->create();
        $file = $this->attachment($quote);
        $component = Livewire::actingAs($viewer)->test(QuoteDownloadCard::class, compact('quote', 'file'));
        Quote::whereKey($quote->id)->update(['status' => \App\Enums\QuoteStatus::ACCEPTED]);

        $component->call('changeFileType', FileTypes::WARRANTY->value)->assertForbidden();

        $this->assertSame(FileTypes::RECEIPT, $file->fresh()->file_type);
    }

    private function viewer(): User
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo([
            Permissions::QUOTE_VIEW_OWN, Permissions::QUOTE_EDIT_OWN,
            Permissions::FILE_VIEW_OWN, Permissions::FILE_EDIT_OWN, Permissions::FILE_DELETE_OWN,
        ]);

        return $viewer;
    }

    private function attachment(Quote $quote, string $name = 'receipt.pdf'): File
    {
        $path = 'quotes/' . $quote->id . '/' . $name;
        Storage::put($path, '%PDF-1.7 test');

        return $quote->files()->create([
            'path' => $path, 'original_name' => $name, 'label' => $name,
            'extension' => 'pdf', 'mime_type' => 'application/pdf',
            'size' => 13, 'file_type' => FileTypes::RECEIPT,
        ]);
    }
}
