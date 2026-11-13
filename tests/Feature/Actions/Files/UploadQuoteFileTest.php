<?php

namespace Tests\Feature\Actions\Files;

use App\Actions\Files\UploadQuoteFile;
use App\Enums\FileTypes;
use App\Models\FileAttachment;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Tests\Feature\FeatureTestCase;

class UploadQuoteFileTest extends FeatureTestCase
{
    public function test_public_upload_logs_only_the_issue_without_a_causer(): void
    {
        Storage::fake();
        $quote = Quote::factory()->forCustomer(User::factory())->create();

        $file = app(UploadQuoteFile::class)->handle(UploadedFile::fake()->create('receipt.pdf', 10, 'application/pdf'), $quote, FileTypes::RECEIPT);

        $entry = Activity::forSubject($quote->issue)->where('description', 'File uploaded')->sole();
        $this->assertNull($entry->causer_id);
        $this->assertSame($file->id, $entry->getProperty('file_id'));
        $this->assertSame(0, Activity::forSubject($file)->where('description', 'File uploaded')->count());
        $this->assertSame(0, Activity::forSubject($quote)->where('description', 'File uploaded')->count());
    }

    public function test_pivot_creation_failure_removes_file_record_and_stored_bytes(): void
    {
        Storage::fake();
        $quote = Quote::factory()->forCustomer(User::factory())->create();
        FileAttachment::creating(function (): void {
            throw new RuntimeException('Cannot attach file');
        });

        try {
            app(UploadQuoteFile::class)->handle(UploadedFile::fake()->create('receipt.pdf', 10, 'application/pdf'), $quote, FileTypes::RECEIPT);
            $this->fail('Attachment failure must propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Cannot attach file', $exception->getMessage());
            $this->assertDatabaseCount('files', 0);
            $this->assertDatabaseCount('fileables', 0);
            Storage::disk()->assertEmpty();
        }
    }

    public function test_caller_rollback_removes_file_link_record_and_stored_bytes(): void
    {
        Storage::fake();
        $quote = Quote::factory()->forCustomer(User::factory())->create();

        try {
            DB::transaction(function () use ($quote): void {
                app(UploadQuoteFile::class)->handle(UploadedFile::fake()->create('receipt.pdf', 10, 'application/pdf'), $quote, FileTypes::RECEIPT);
                throw new RuntimeException('Later failure');
            });
            $this->fail('Caller failure must propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Later failure', $exception->getMessage());
            $this->assertDatabaseCount('files', 0);
            $this->assertDatabaseCount('fileables', 0);
            Storage::disk()->assertEmpty();
        }
    }
}
