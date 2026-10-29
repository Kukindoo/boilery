<?php

namespace Tests\Feature\Quote;

use App\Enums\FileTypes;
use App\Enums\Permissions;
use App\Enums\Roles;
use App\Models\File;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\FeatureTestCase;

class RequestedQuoteFilePreviewControllerTest extends FeatureTestCase
{
    /** @return array<string, array{?Permissions, bool, bool, ?Permissions}> */
    public static function previewPermissions(): array
    {
        return [
            'view all, own quote' => [Permissions::QUOTE_VIEW_ALL, true, true, Permissions::FILE_VIEW_ALL],
            'view all, another user quote' => [Permissions::QUOTE_VIEW_ALL, false, true, Permissions::FILE_VIEW_ALL],
            'view own, own quote' => [Permissions::QUOTE_VIEW_OWN, true, true, Permissions::FILE_VIEW_OWN],
            'view own, another user quote' => [Permissions::QUOTE_VIEW_OWN, false, false, Permissions::FILE_VIEW_ALL],
            'view all quotes without file permission' => [Permissions::QUOTE_VIEW_ALL, true, false, null],
            'view own quote without file permission' => [Permissions::QUOTE_VIEW_OWN, true, false, null],
            'view all files without quote permission' => [null, true, false, Permissions::FILE_VIEW_ALL],
            'view own files without quote permission' => [null, true, false, Permissions::FILE_VIEW_OWN],
            'view all quotes and own files, own quote' => [Permissions::QUOTE_VIEW_ALL, true, true, Permissions::FILE_VIEW_OWN],
            'view all quotes and own files, another user quote' => [Permissions::QUOTE_VIEW_ALL, false, false, Permissions::FILE_VIEW_OWN],
            'no permission, own quote' => [null, true, false, null],
            'no permission, another user quote' => [null, false, false, null],
            'view index, own quote' => [Permissions::QUOTE_VIEW_INDEX, true, false, null],
            'view index, another user quote' => [Permissions::QUOTE_VIEW_INDEX, false, false, null],
            'print all, own quote' => [Permissions::QUOTE_PRINT_ALL, true, false, null],
            'print all, another user quote' => [Permissions::QUOTE_PRINT_ALL, false, false, null],
            'edit all, own quote' => [Permissions::QUOTE_EDIT_ALL, true, false, null],
            'edit all, another user quote' => [Permissions::QUOTE_EDIT_ALL, false, false, null],
            'edit own, own quote' => [Permissions::QUOTE_EDIT_OWN, true, false, null],
            'edit own, another user quote' => [Permissions::QUOTE_EDIT_OWN, false, false, null],
            'quote_delete_all, own quote' => [Permissions::QUOTE_DELETE_ALL, true, false, null],
            'quote_delete_all, another quote' => [Permissions::QUOTE_DELETE_ALL, false, false, null],
            'quote_delete_own, own quote' => [Permissions::QUOTE_DELETE_OWN, true, false, null],
            'quote_delete_own, another quote' => [Permissions::QUOTE_DELETE_OWN, false, false, null],
            'quote_create_all, own quote' => [Permissions::QUOTE_CREATE_ALL, true, false, null],
            'quote_create_own, own quote' => [Permissions::QUOTE_CREATE_OWN, true, false, null],
            'quote_create_all, another quote' => [Permissions::QUOTE_CREATE_ALL, false, false, null],
            'quote_create_own, another quote' => [Permissions::QUOTE_CREATE_OWN, false, false, null],
            'quote_restore_all, own quote' => [Permissions::QUOTE_RESTORE_ALL, true, false, null],
            'quote_restore_all, another quote' => [Permissions::QUOTE_RESTORE_ALL, false, false, null],
            'quote_restore_own, own quote' => [Permissions::QUOTE_RESTORE_OWN, true, false, null],
            'quote_restore_own, another quote' => [Permissions::QUOTE_RESTORE_OWN, false, false, null],
            'quote_force_delete_all, own quote' => [Permissions::QUOTE_FORCE_DELETE_ALL, true, false, null],
            'quote_force_delete_all, another quote' => [Permissions::QUOTE_FORCE_DELETE_ALL, false, false, null],
            'quote_force_delete_own, own quote' => [Permissions::QUOTE_FORCE_DELETE_OWN, true, false, null],
            'quote_force_delete_own, another quote' => [Permissions::QUOTE_FORCE_DELETE_OWN, false, false, null],
        ];
    }

    public function test_admin_can_preview_uploaded_jpeg(): void
    {
        Storage::fake('local');
        config(['filesystems.default' => 'local']);
        $user = User::factory()->create();
        $user->assignRole(Roles::ADMIN);
        $quote = Quote::factory()->for(User::factory())->create();
        $file = $this->createFile($quote);
        $file->update(['mime_type' => 'image/jpeg', 'extension' => 'jpg']);
        $image = \Illuminate\Http\UploadedFile::fake()->image('receipt.jpg')->getContent();
        Storage::disk('local')->put($file->path, $image);

        $this->actingAs($user)->get(route('quotes.files.preview', [$quote, $file]))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg')
            ->assertStreamedContent($image);
    }

    #[DataProvider('previewPermissions')]
    public function test_preview_checks_permissions_and_quote_ownership(?Permissions $permission, bool $ownsQuote, bool $allowed, ?Permissions $filePermission): void
    {
        Storage::fake('local');
        config(['filesystems.default' => 'local']);
        $user = User::factory()->create();
        $quote = Quote::factory()->for($ownsQuote ? $user : User::factory()->create())->create();
        $file = $this->createFile($quote);
        Storage::disk('local')->put($file->path, 'Quote attachment');

        if ($permission !== null) {
            $user->givePermissionTo($permission);
        }

        if ($filePermission !== null) {
            $user->givePermissionTo($filePermission);
        }

        $response = $this->actingAs($user)->get(route('quotes.files.preview', [$quote, $file]));

        if ($allowed) {
            $response->assertOk()
                ->assertHeader('Content-Type', 'application/pdf')
                ->assertHeader('Content-Disposition', 'inline')
                ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
                ->assertStreamedContent('Quote attachment');
        } else {
            $response->assertForbidden();
        }
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $quote = Quote::factory()->for(User::factory())->create();
        $file = $this->createFile($quote);

        $this->get(route('quotes.files.preview', [$quote, $file]))
            ->assertRedirect(route('login'));
    }

    public function test_file_from_another_quote_returns_404_even_with_quote_and_file_view_all_permissions(): void
    {
        Storage::fake('local');
        config(['filesystems.default' => 'local']);
        $user = User::factory()->create();
        $user->givePermissionTo([Permissions::QUOTE_VIEW_ALL, Permissions::FILE_VIEW_ALL]);
        $quote = Quote::factory()->for($user)->create();
        $otherQuote = Quote::factory()->for($user)->create();
        $file = $this->createFile($otherQuote);
        Storage::disk('local')->put($file->path, 'Other quote attachment');

        $this->actingAs($user)->get(route('quotes.files.preview', [$quote, $file]))
            ->assertNotFound();
    }

    public function test_missing_stored_file_returns_404(): void
    {
        Storage::fake('local');
        config(['filesystems.default' => 'local']);
        $user = User::factory()->create();
        $user->givePermissionTo([Permissions::QUOTE_VIEW_OWN, Permissions::FILE_VIEW_OWN]);
        $quote = Quote::factory()->for($user)->create();
        $file = $this->createFile($quote);

        $this->actingAs($user)->get(route('quotes.files.preview', [$quote, $file]))
            ->assertNotFound();
    }

    public function test_missing_file_record_returns_404(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo([Permissions::QUOTE_VIEW_OWN, Permissions::FILE_VIEW_OWN]);
        $quote = Quote::factory()->for($user)->create();

        $this->actingAs($user)->get(route('quotes.files.preview', [$quote, 999999]))
            ->assertNotFound();
    }

    public function test_missing_quote_returns_404(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo([Permissions::QUOTE_VIEW_ALL, Permissions::FILE_VIEW_ALL]);
        $quote = Quote::factory()->for($user)->create();
        $file = $this->createFile($quote);

        $this->actingAs($user)->get(route('quotes.files.preview', [999999, $file]))
            ->assertNotFound();
    }

    private function createFile(Quote $quote): File
    {
        return $quote->files()->create([
            'original_name' => 'receipt.pdf',
            'file_type' => FileTypes::RECEIPT,
            'extension' => 'pdf',
            'size' => 16,
            'mime_type' => 'application/pdf',
            'path' => 'quotes/' . $quote->id . '/receipt.pdf',
            'label' => 'Receipt',
        ]);
    }
}
