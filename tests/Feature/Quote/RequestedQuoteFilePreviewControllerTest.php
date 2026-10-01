<?php

namespace Tests\Feature\Quote;

use App\Enums\FileTypes;
use App\Enums\Permissions;
use App\Models\File;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\FeatureTestCase;

class RequestedQuoteFilePreviewControllerTest extends FeatureTestCase
{
    /** @return array<string, array{?Permissions, bool, bool}> */
    public static function previewPermissions(): array
    {
        return [
            'view all, own quote' => [Permissions::QUOTE_VIEW_ALL, true, true],
            'view all, another user quote' => [Permissions::QUOTE_VIEW_ALL, false, true],
            'view own, own quote' => [Permissions::QUOTE_VIEW_OWN, true, true],
            'view own, another user quote' => [Permissions::QUOTE_VIEW_OWN, false, false],
            'no permission, own quote' => [null, true, false],
            'no permission, another user quote' => [null, false, false],
            'view index, own quote' => [Permissions::QUOTE_VIEW_INDEX, true, false],
            'view index, another user quote' => [Permissions::QUOTE_VIEW_INDEX, false, false],
            'print all, own quote' => [Permissions::QUOTE_PRINT_ALL, true, false],
            'print all, another user quote' => [Permissions::QUOTE_PRINT_ALL, false, false],
            'edit all, own quote' => [Permissions::QUOTE_EDIT_ALL, true, false],
            'edit all, another user quote' => [Permissions::QUOTE_EDIT_ALL, false, false],
            'edit own, own quote' => [Permissions::QUOTE_EDIT_OWN, true, false],
            'edit own, another user quote' => [Permissions::QUOTE_EDIT_OWN, false, false],
        ];
    }

    #[DataProvider('previewPermissions')]
    public function test_preview_checks_permissions_and_quote_ownership(?Permissions $permission, bool $ownsQuote, bool $allowed): void
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

    public function test_file_from_another_quote_returns_404_even_with_view_all_permission(): void
    {
        Storage::fake('local');
        config(['filesystems.default' => 'local']);
        $user = User::factory()->create();
        $user->givePermissionTo(Permissions::QUOTE_VIEW_ALL);
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
        $user->givePermissionTo(Permissions::QUOTE_VIEW_OWN);
        $quote = Quote::factory()->for($user)->create();
        $file = $this->createFile($quote);

        $this->actingAs($user)->get(route('quotes.files.preview', [$quote, $file]))
            ->assertNotFound();
    }

    public function test_missing_file_record_returns_404(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permissions::QUOTE_VIEW_OWN);
        $quote = Quote::factory()->for($user)->create();

        $this->actingAs($user)->get(route('quotes.files.preview', [$quote, 999999]))
            ->assertNotFound();
    }

    public function test_missing_quote_returns_404(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permissions::QUOTE_VIEW_ALL);
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
