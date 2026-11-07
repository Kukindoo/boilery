<?php

namespace Tests\Feature\Works;

use App\Enums\FileTypes;
use App\Enums\Permissions;
use App\Livewire\Files\FilePreviewCard;
use App\Models\File;
use App\Models\Quote;
use App\Models\User;
use App\Models\Work;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Feature\FeatureTestCase;

class WorkFileCardTest extends FeatureTestCase
{
    public function test_deleting_an_attachment_detaches_it_and_preserves_the_shared_file(): void
    {
        Storage::fake();
        $work = Work::factory()->create();
        $file = $this->attachment($work);
        $quote = Quote::factory()->create();
        $quote->files()->attach($file->id);

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(FilePreviewCard::class, ['model' => $work, 'file' => $file])
            ->assertSee('Odebrat přílohu')
            ->call('deleteFile')
            ->assertRedirect(route('works.show', $work));

        $this->assertModelExists($file);
        $this->assertFalse($file->isAttachedTo($work));
        $this->assertTrue($file->isAttachedTo($quote));
        Storage::disk()->assertExists($file->path);
        $this->assertFalse($work->hasServiceProtocol());
    }

    public function test_viewing_a_work_does_not_allow_deleting_its_files(): void
    {
        Storage::fake();
        $user = User::factory()->customer()->create();
        $user->givePermissionTo(Permissions::WORK_VIEW_OWN->label());
        $work = Work::factory()->for($user)->create();
        $file = $this->attachment($work);

        Livewire::actingAs($user)->test(FilePreviewCard::class, ['model' => $work, 'file' => $file])
            ->assertDontSee('Odebrat přílohu')
            ->call('deleteFile')->assertForbidden();

        $this->assertModelExists($file);
        Storage::disk()->assertExists($file->path);
    }

    public function test_a_file_from_another_work_is_rejected(): void
    {
        Storage::fake();
        $work = Work::factory()->create();
        $file = $this->attachment(Work::factory()->create());

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(FilePreviewCard::class, ['model' => $work, 'file' => $file])->assertNotFound();

        $this->assertModelExists($file);
    }

    public function test_attachment_can_be_downloaded_and_its_type_changed(): void
    {
        Storage::fake();
        $work = Work::factory()->create();
        $file = $this->attachment($work);

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(FilePreviewCard::class, ['model' => $work, 'file' => $file])
            ->assertSee('Změnit typ')
            ->assertSee('delete-attachment-' . $file->id)
            ->call('downloadFile')->assertFileDownloaded('protocol.pdf')
            ->call('changeFileType', FileTypes::RECEIPT->value)
            ->call('downloadFile')->assertFileDownloaded('protocol.pdf');

        $this->assertSame(FileTypes::RECEIPT, $file->fresh()->file_type);
        $this->assertSame('protocol.pdf', $file->fresh()->label);
    }

    public function test_read_only_customer_cannot_change_attachment_type(): void
    {
        Storage::fake();
        $user = User::factory()->customer()->create();
        $user->givePermissionTo(Permissions::WORK_VIEW_OWN->label());
        $work = Work::factory()->for($user)->create();
        $file = $this->attachment($work);

        Livewire::actingAs($user)->test(FilePreviewCard::class, ['model' => $work, 'file' => $file])
            ->assertDontSee('Změnit typ')
            ->call('changeFileType', FileTypes::RECEIPT->value)->assertForbidden();

        $this->assertSame(FileTypes::SERVICE_PROTOCOL, $file->fresh()->file_type);
    }

    private function attachment(Work $work): File
    {
        Storage::put('protocol.pdf', '%PDF-1.7 test');

        return $work->files()->create([
            'path' => 'protocol.pdf', 'original_name' => 'protocol.pdf',
            'label' => 'protocol.pdf', 'extension' => 'pdf',
            'mime_type' => 'application/pdf', 'size' => 13,
            'file_type' => FileTypes::SERVICE_PROTOCOL,
        ]);
    }
}
