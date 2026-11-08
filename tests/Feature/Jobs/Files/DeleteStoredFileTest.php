<?php

namespace Tests\Feature\Jobs\Files;

use App\Jobs\Files\DeleteStoredFile;
use App\Models\File;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\Feature\FeatureTestCase;

class DeleteStoredFileTest extends FeatureTestCase
{
    public function test_deletes_orphaned_bytes_and_can_be_retried_after_success(): void
    {
        Storage::fake('documents');
        Storage::disk('documents')->put('orphan.pdf', 'content');
        $job = new DeleteStoredFile('orphan.pdf', 'documents');

        $job->handle();
        $job->handle();

        Storage::disk('documents')->assertMissing('orphan.pdf');
    }

    public function test_retains_a_path_still_referenced_by_a_file_record(): void
    {
        Storage::fake('documents');
        $file = File::factory()->create();
        Storage::disk('documents')->put($file->path, 'content');

        (new DeleteStoredFile($file->path, 'documents'))->handle();

        Storage::disk('documents')->assertExists($file->path);
    }

    public function test_storage_failure_throws_and_a_later_retry_removes_the_file(): void
    {
        $storage = Storage::fake('documents');
        $storage->put('orphan.pdf', 'content');
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('delete')->once()->with('orphan.pdf')->andReturnFalse();
        Storage::shouldReceive('disk')->twice()->with('documents')->andReturn($disk, $storage);
        $job = new DeleteStoredFile('orphan.pdf', 'documents');

        try {
            $job->handle();
            $this->fail('Storage failure must propagate so the queue can retry.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Could not delete the orphaned stored file.', $exception->getMessage());
            $storage->assertExists('orphan.pdf');
        }

        $job->handle();

        $storage->assertMissing('orphan.pdf');
    }
}
