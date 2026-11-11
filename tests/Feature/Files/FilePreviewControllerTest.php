<?php

namespace Tests\Feature\Files;

use App\Enums\Permissions;
use App\Models\File;
use App\Models\Quote;
use App\Models\User;
use App\Models\Work;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\FeatureTestCase;

class FilePreviewControllerTest extends FeatureTestCase
{
    public function test_image_component_uses_the_shared_route_for_both_parent_types(): void
    {
        $file = File::factory()->make(['id' => 42, 'mime_type' => 'image/jpeg']);

        foreach (['quote' => Quote::factory()->make(['id' => 12]), 'work' => Work::factory()->make(['id' => 12])] as $type => $model) {
            $this->blade('<x-files.file-preview :file="$file" :model="$model" />', compact('file', 'model'))
                ->assertSee('<img', false)
                ->assertSee(route('files.preview', [$type, $model, $file]), false)
                ->assertDontSee('<iframe', false);
        }
    }

    public function test_component_does_not_embed_unsupported_file_types(): void
    {
        $file = File::factory()->make(['mime_type' => 'text/plain']);
        $model = Work::factory()->make();

        $this->blade('<x-files.file-preview :file="$file" :model="$model" />', compact('file', 'model'))
            ->assertDontSee('<img', false)
            ->assertDontSee('<iframe', false);
    }

    public function test_work_attachment_is_streamed_inline(): void
    {
        Storage::fake();
        $work = Work::factory()->create();
        $file = File::factory()->create(['path' => 'protocol.pdf', 'mime_type' => 'application/pdf']);
        $work->files()->attach($file);
        Storage::put($file->path, '%PDF-1.7 test');

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('files.preview', ['work', $work, $file]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertStreamedContent('%PDF-1.7 test');
    }

    public function test_work_preview_requires_parent_and_file_permissions(): void
    {
        Storage::fake();
        $user = User::factory()->create();
        $work = Work::factory()->forCustomer($user)->create();
        $file = File::factory()->create();
        $work->files()->attach($file);
        Storage::put($file->path, 'attachment');

        $user->givePermissionTo(Permissions::FILE_VIEW_ALL);
        $this->actingAs($user)->get(route('files.preview', ['work', $work, $file]))
            ->assertForbidden();

        $user->revokePermissionTo(Permissions::FILE_VIEW_ALL);
        $user->givePermissionTo(Permissions::WORK_VIEW_ALL);
        $this->get(route('files.preview', ['work', $work, $file]))->assertForbidden();
    }

    public function test_file_attached_to_another_work_returns_404(): void
    {
        $work = Work::factory()->create();
        $otherWork = Work::factory()->create();
        $file = File::factory()->create();
        $otherWork->files()->attach($file);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('files.preview', ['work', $work, $file]))->assertNotFound();
    }

    public function test_unsupported_model_type_returns_404(): void
    {
        $file = File::factory()->create();
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('files.preview', ['user', 1, $file]))->assertNotFound();
    }
}
