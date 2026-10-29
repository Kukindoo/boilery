<?php

namespace Tests\Feature;

use App\Enums\Roles;
use App\Livewire\Quotes\SingleFileUpload;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;

class SingleFileUploadTest extends FeatureTestCase
{
    /** @return array<string, array{Roles, bool}> */
    public static function allowedUploads(): array
    {
        return [
            'admin on another quote' => [Roles::ADMIN, false],
            'stakeholder on another quote' => [Roles::STAKEHOLDER, false],
            'customer on own quote' => [Roles::CUSTOMER, true],
        ];
    }

    #[DataProvider('allowedUploads')]
    public function test_authorized_roles_can_upload_to_a_quote(Roles $role, bool $ownsQuote): void
    {
        Storage::fake('local');
        config(['filesystems.default' => 'local']);
        $user = User::factory()->create();
        $user->assignRole($role);
        $quote = Quote::factory()->accepted()->for($ownsQuote ? $user : User::factory())->create();

        Livewire::actingAs($user)->test(SingleFileUpload::class, ['quote' => $quote])
            ->set('form.file', UploadedFile::fake()->image('receipt.jpg'))
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect(route('quotes.show', $quote));

        $this->assertDatabaseHas('files', ['quote_id' => $quote->id, 'original_name' => 'receipt.jpg']);
        Storage::disk('local')->assertExists($quote->files()->sole()->path);
    }

    public function test_customer_cannot_upload_to_another_customers_quote(): void
    {
        Storage::fake('local');
        config(['filesystems.default' => 'local']);
        $user = User::factory()->create();
        $user->assignRole(Roles::CUSTOMER);
        $quote = Quote::factory()->for(User::factory())->create();

        Livewire::actingAs($user)->test(SingleFileUpload::class, ['quote' => $quote])
            ->set('form.file', UploadedFile::fake()->image('receipt.jpg'))
            ->call('submit')
            ->assertForbidden();

        $this->assertDatabaseMissing('files', ['quote_id' => $quote->id]);
        Storage::disk('local')->assertDirectoryEmpty('files');
    }

    public function test_user_without_upload_permission_cannot_upload_to_own_quote(): void
    {
        $user = User::factory()->create();
        $quote = Quote::factory()->for($user)->create();

        Livewire::actingAs($user)->test(SingleFileUpload::class, ['quote' => $quote])
            ->call('submit')
            ->assertForbidden();

        $this->assertDatabaseMissing('files', ['quote_id' => $quote->id]);
    }

    public function test_upload_still_requires_a_valid_file(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Roles::CUSTOMER);
        $quote = Quote::factory()->for($user)->create();

        Livewire::actingAs($user)->test(SingleFileUpload::class, ['quote' => $quote])
            ->call('submit')
            ->assertHasErrors(['form.file' => 'required']);

        $this->assertDatabaseMissing('files', ['quote_id' => $quote->id]);
    }
}
