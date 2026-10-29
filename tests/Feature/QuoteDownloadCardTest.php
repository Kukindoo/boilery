<?php

namespace Tests\Feature;

use App\Enums\FileTypes;
use App\Enums\Roles;
use App\Livewire\Quotes\QuoteDownloadCard;
use App\Models\Quote;
use App\Models\User;
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
}
