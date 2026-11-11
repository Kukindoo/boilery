<?php

namespace Tests\Feature;

use App\Actions\Files\DetachFile;
use App\Actions\Files\UploadFile;
use App\Enums\FileTypes;
use App\Models\Issue;
use App\Models\Quote;
use App\Models\User;
use App\Models\Work;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class IssueFilesTest extends FeatureTestCase
{
    public function test_uploads_to_issue_are_shared_by_all_its_quotes_and_works_only(): void
    {
        Storage::fake();
        $this->actingAs(User::factory()->admin()->create());
        $issue = Issue::factory()->create();
        $quotes = Quote::factory()->count(2)->for($issue)->create();
        $works = Work::factory()->count(2)->for($issue)->create();
        $unrelated = Work::factory()->create();

        $file = app(UploadFile::class)->handle(UploadedFile::fake()->image('receipt.jpg'), $issue, FileTypes::RECEIPT);

        $this->assertTrue($file->attachments()->sole()->fileable->is($issue));
        foreach (Quote::with('files')->whereKey($quotes->modelKeys())->get() as $quote) {
            $this->assertTrue($quote->files->sole()->is($file));
        }
        foreach (Work::with('files')->whereKey($works->modelKeys())->get() as $work) {
            $this->assertTrue($work->files->sole()->is($file));
        }
        $this->assertCount(0, $unrelated->files);
        $this->assertTrue($file->isOwnedBy($issue->user));
    }

    public function test_permanently_deleting_quote_and_work_preserves_issue_files(): void
    {
        Storage::fake();
        $this->actingAs(User::factory()->admin()->create());
        $issue = Issue::factory()->create();
        $quote = Quote::factory()->for($issue)->create();
        $work = Work::factory()->for($issue)->create();
        $file = app(UploadFile::class)->handle(UploadedFile::fake()->image('receipt.jpg'), $work, FileTypes::RECEIPT);

        $quote->forceDelete();
        $work->forceDelete();

        $this->assertModelExists($file);
        $this->assertTrue($issue->files()->sole()->is($file));
        Storage::disk()->assertExists($file->path);
    }

    public function test_detaching_from_work_removes_the_shared_issue_attachment(): void
    {
        Storage::fake();
        $this->actingAs(User::factory()->admin()->create());
        $quote = Quote::factory()->create();
        $work = Work::factory()->for($quote->issue)->create();
        $file = app(UploadFile::class)->handle(UploadedFile::fake()->image('receipt.jpg'), $quote, FileTypes::RECEIPT);

        app(DetachFile::class)->handle($work, $file);

        $this->assertCount(0, $quote->fresh()->files);
        $this->assertCount(0, $work->fresh()->files);
        $this->assertModelMissing($file);
    }
}
