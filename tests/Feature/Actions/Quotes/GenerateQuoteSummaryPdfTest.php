<?php

namespace Tests\Feature\Actions\Quotes;

use App\Actions\Quotes\GenerateQuoteSummaryPdf;
use App\Enums\BoilerManufacturers;
use App\Enums\FileTypes;
use App\Models\File;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Spatie\LaravelPdf\Exceptions\CouldNotGeneratePdf;
use Tests\Feature\FeatureTestCase;

class GenerateQuoteSummaryPdfTest extends FeatureTestCase
{
    private const ENDPOINT = 'https://api.cloudflare.com/client/v4/accounts/test-account/browser-rendering/pdf';

    private const PDF_CONTENT = "%PDF-1.7\nquote summary\xC3\xA9\n%%EOF";

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'filesystems.default' => 'local',
            'laravel-pdf.driver' => 'cloudflare',
            'laravel-pdf.cloudflare.account_id' => 'test-account',
            'laravel-pdf.cloudflare.api_token' => 'test-token',
            'laravel-pdf.cache.automatic' => false,
        ]);
        Http::preventStrayRequests();
    }

    public function test_summary_label_uses_the_assigned_customer_instead_of_quote_contact_details(): void
    {
        Storage::fake('local');
        Http::fake([self::ENDPOINT => Http::response(self::PDF_CONTENT, 200)]);
        $customer = User::factory()->create(['name' => 'Jiří Novák']);
        $quote = Quote::factory()->for($customer)->create([
            'first_name' => 'Jan',
            'last_name' => 'Svoboda',
        ]);

        $summary = app(GenerateQuoteSummaryPdf::class)->handle($quote);

        $this->assertSame("Jiri_Novak_{$quote->id}_shrnuti_poptavky.pdf", $summary->label);
        Storage::disk()->assertExists($summary->path);
    }

    public function test_saves_quote_summary_on_the_default_disk_with_file_metadata(): void
    {
        Storage::fake('quote-documents');
        config([
            'filesystems.default' => 'quote-documents',
            'app.files.attachments_save_directory' => 'custom/attachments',
        ]);
        Http::fake([self::ENDPOINT => Http::response(self::PDF_CONTENT, 200)]);
        $quote = Quote::factory()->for(User::factory()->state(['name' => 'Jiří Novák']))->create([
            'first_name' => 'Jiří',
            'last_name' => 'Novák',
            'boiler_manufacturer' => BoilerManufacturers::DRAZICE,
            'message' => '<script>alert("test")</script>',
        ]);

        $file = app(GenerateQuoteSummaryPdf::class)->handle($quote);

        $this->assertModelExists($file);
        $this->assertTrue($file->isAttachedTo($quote));
        $this->assertTrue($quote->files()->sole()->is($file));
        $this->assertDatabaseHas('files', [
            'id' => $file->id,
            'original_name' => "Jiri_Novak_{$quote->id}_shrnuti_poptavky.pdf",
            'label' => "Jiri_Novak_{$quote->id}_shrnuti_poptavky.pdf",
            'extension' => 'pdf',
            'mime_type' => 'application/pdf',
            'file_type' => 'quote-summary',
            'size' => 30,
        ]);
        $this->assertSame(FileTypes::QUOTE_SUMMARY, $file->file_type);
        $this->assertStringStartsWith('custom/attachments/', $file->path);
        Storage::disk()->assertExists($file->path);
        $this->assertSame(self::PDF_CONTENT, Storage::disk()->get($file->path));
        Http::assertSent(fn (Request $request): bool => $request->url() === self::ENDPOINT
            && $request['pdfOptions']['format'] === 'a4'
            && str_contains($request['html'], 'Jiří Novák')
            && str_contains($request['html'], 'Dražice')
            && str_contains($request['html'], '&lt;script&gt;')
            && ! str_contains($request['html'], '<script>'));
    }

    public function test_repeated_generation_preserves_previous_files_and_handles_missing_optional_details(): void
    {
        Storage::fake('local');
        Http::fake([self::ENDPOINT => Http::response(self::PDF_CONTENT, 200)]);
        $quote = Quote::factory()->for(User::factory())->create(['address' => null]);
        $action = app(GenerateQuoteSummaryPdf::class);

        $first = $action->handle($quote);
        $second = $action->handle($quote);

        $this->assertNotSame($first->path, $second->path);
        $this->assertSame(2, $quote->files()->count());
        Storage::disk()->assertExists([$first->path, $second->path]);
        Http::assertSent(fn (Request $request): bool => str_contains($request['html'], 'Neuvedeno')
            && ! str_contains($request['html'], 'Přílohy'));
    }

    public function test_includes_quote_attachments_and_embeds_images_but_excludes_existing_summaries(): void
    {
        Storage::fake('local');
        Http::fake([self::ENDPOINT => Http::response(self::PDF_CONTENT, 200)]);
        $quote = Quote::factory()->for(User::factory())->create();
        $image = $this->createAttachment(UploadedFile::fake()->image('boiler.png'), $quote, FileTypes::BOILER_LABEL);
        $image->update(['label' => '<script>attachment</script>.png']);
        $document = $this->createAttachment(UploadedFile::fake()->create('receipt.pdf', 10, 'application/pdf'), $quote, FileTypes::RECEIPT);
        $summary = $this->createAttachment(UploadedFile::fake()->create('old-summary.pdf', 10, 'application/pdf'), $quote, FileTypes::QUOTE_SUMMARY);
        $otherQuote = Quote::factory()->for(User::factory())->create();
        $otherImage = $this->createAttachment(UploadedFile::fake()->image('other.png'), $otherQuote, FileTypes::BOILER_LABEL);
        $imageSource = 'data:image/png;base64,' . base64_encode(Storage::disk()->get($image->path));
        $quote->load('files');

        app(GenerateQuoteSummaryPdf::class)->handle($quote);

        Http::assertSent(fn (Request $request): bool => str_contains($request['html'], 'Přílohy')
            && str_contains($request['html'], $imageSource)
            && str_contains($request['html'], '&lt;script&gt;attachment&lt;/script&gt;.png')
            && ! str_contains($request['html'], '<script>attachment</script>')
            && str_contains($request['html'], $document->label)
            && str_contains($request['html'], 'application/pdf')
            && ! str_contains($request['html'], $summary->label)
            && ! str_contains($request['html'], $otherImage->label));
        Storage::disk()->assertExists([$image->path, $document->path, $summary->path]);
    }

    public function test_missing_image_prevents_generating_an_incomplete_summary(): void
    {
        Storage::fake('local');
        $quote = Quote::factory()->for(User::factory())->create();
        $image = $this->createAttachment(UploadedFile::fake()->image('boiler.png'), $quote, FileTypes::BOILER_LABEL);
        Storage::disk()->delete($image->path);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not read quote attachment: ' . $image->label);

        try {
            app(GenerateQuoteSummaryPdf::class)->handle($quote);
        } finally {
            Http::assertNothingSent();
            $this->assertFalse($quote->hasQuoteSummary());
            Storage::disk()->assertEmpty();
        }
    }

    public function test_rendering_failure_does_not_create_a_file_or_database_record(): void
    {
        Storage::fake('local');
        Http::fake([self::ENDPOINT => Http::response('Rendering failed', 500)]);
        $quote = Quote::factory()->for(User::factory())->create();

        $this->expectException(CouldNotGeneratePdf::class);

        try {
            app(GenerateQuoteSummaryPdf::class)->handle($quote);
        } finally {
            $this->assertTrue($quote->files()->doesntExist());
            Storage::disk()->assertEmpty();
        }
    }

    public function test_storage_failure_does_not_create_a_database_record(): void
    {
        Http::fake([self::ENDPOINT => Http::response(self::PDF_CONTENT, 200)]);
        $quote = Quote::factory()->for(User::factory())->create();
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('put')->once()->with(Mockery::type('string'), self::PDF_CONTENT)->andReturnFalse();
        Storage::shouldReceive('disk')->withNoArgs()->andReturn($disk);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not store the quote summary PDF.');

        try {
            app(GenerateQuoteSummaryPdf::class)->handle($quote);
        } finally {
            $this->assertTrue($quote->files()->doesntExist());
        }
    }

    public function test_attachment_link_failure_rolls_back_the_record_and_removes_the_pdf(): void
    {
        Storage::fake('local');
        Http::fake([self::ENDPOINT => Http::response(self::PDF_CONTENT, 200)]);
        $quote = Quote::factory()->for(User::factory())->create();
        \App\Models\FileAttachment::creating(function (): void {
            throw new RuntimeException('Cannot attach PDF');
        });

        try {
            app(GenerateQuoteSummaryPdf::class)->handle($quote);
            $this->fail('Attachment failure must propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Cannot attach PDF', $exception->getMessage());
            $this->assertDatabaseCount('files', 0);
            $this->assertDatabaseCount('fileables', 0);
            Storage::disk()->assertEmpty();
        }
    }

    public function test_record_creation_failure_removes_the_stored_pdf(): void
    {
        Storage::fake('local');
        Http::fake([self::ENDPOINT => Http::response(self::PDF_CONTENT, 200)]);
        $quote = Quote::factory()->for(User::factory())->create();
        File::creating(function (File $file): void {
            throw new RuntimeException('File record could not be saved.');
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File record could not be saved.');

        try {
            app(GenerateQuoteSummaryPdf::class)->handle($quote);
        } finally {
            $this->assertTrue($quote->files()->doesntExist());
            Storage::disk()->assertEmpty();
        }
    }

    private function createAttachment(UploadedFile $upload, Quote $quote, FileTypes $fileType): File
    {
        return $quote->files()->create([
            'path' => $upload->store('files/attachments'),
            'original_name' => $upload->getClientOriginalName(),
            'label' => $upload->getClientOriginalName(),
            'extension' => $upload->getClientOriginalExtension(),
            'mime_type' => $upload->getMimeType(),
            'size' => $upload->getSize(),
            'file_type' => $fileType,
        ]);
    }
}
