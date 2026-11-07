<?php

namespace Tests\Feature\Files;

use App\Enums\Permissions;
use App\Livewire\Files\SingleFileUpload;
use App\Livewire\Files\UploadFileButton;
use App\Models\Quote;
use App\Models\User;
use App\Models\Work;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\FeatureTestCase;

class UploadFileButtonTest extends FeatureTestCase
{
    public static function parentTypes(): array
    {
        return ['quote' => ['quote'], 'work' => ['work']];
    }

    #[DataProvider('parentTypes')]
    public function test_upload_button_renders_the_upload_form_for_both_models(string $type): void
    {
        $model = $type === 'quote' ? Quote::factory()->for(User::factory())->create() : Work::factory()->create();

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(UploadFileButton::class, compact('model'))
            ->assertSee('Nahrát soubor')
            ->assertSeeLivewire(SingleFileUpload::class);
    }

    public function test_upload_button_and_form_are_hidden_without_upload_permission(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permissions::QUOTE_VIEW_ALL);
        $model = Quote::factory()->create();

        Livewire::actingAs($user)->test(UploadFileButton::class, compact('model'))
            ->assertDontSee('Nahrát')
            ->assertDontSeeLivewire(SingleFileUpload::class);
    }

    public function test_upload_button_requires_access_to_the_parent(): void
    {
        $model = Quote::factory()->create();

        Livewire::actingAs(User::factory()->create())
            ->test(UploadFileButton::class, compact('model'))->assertForbidden();
    }

    #[DataProvider('parentTypes')]
    public function test_upload_form_accepts_a_custom_modal_name_and_returns_to_the_parent(string $type): void
    {
        Storage::fake();
        $model = $type === 'quote' ? Quote::factory()->for(User::factory())->create() : Work::factory()->create();

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(SingleFileUpload::class, ['model' => $model, 'modalName' => 'custom-upload'])
            ->set('form.file', UploadedFile::fake()->image('receipt.jpg'))
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect(route($type === 'quote' ? 'quotes.show' : 'works.show', $model));

        Storage::assertExists($model->files()->sole()->path);
    }
}
