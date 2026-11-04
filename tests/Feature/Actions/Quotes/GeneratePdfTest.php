<?php

namespace Tests\Feature\Actions\Quotes;

use App\Actions\Files\UploadQuoteFile;
use App\Actions\Quotes\GeneratePdf;
use App\Enums\BoilerManufacturers;
use App\Enums\FileTypes;
use App\Models\File;
use App\Models\Quote;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Spatie\LaravelPdf\Exceptions\CouldNotGeneratePdf;
use Tests\Feature\FeatureTestCase;

class GeneratePdfTest extends FeatureTestCase
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

    public function test_saves_quote_summary_on_the_default_disk_with_file_metadata(): void
    {
        Storage::fake('quote-documents');
        config([
            'filesystems.default' => 'quote-documents',
            'app.files.quote_save_directory' => 'custom/quotes/{quote_id}',
        ]);
        Http::fake([self::ENDPOINT => Http::response(self::PDF_CONTENT, 200)]);
        $quote = Quote::factory()->create([
            'first_name' => 'Jiří',
            'last_name' => 'Novák',
            'boiler_manufacturer' => BoilerManufacturers::DRAZICE,
            'message' => '<script>alert("test")</script>',
        ]);

        $file = app(GeneratePdf::class)->handle($quote);

        $this->assertModelExists($file);
        $this->assertTrue($quote->files()->sole()->is($file));
        $this->assertDatabaseHas('files', [
            'id' => $file->id,
            'quote_id' => $quote->id,
            'work_id' => null,
            'original_name' => "Jiri_Novak_{$quote->id}_shrnuti_poptavky.pdf",
            'label' => "Jiri_Novak_{$quote->id}_shrnuti_poptavky.pdf",
            'extension' => 'pdf',
            'mime_type' => 'application/pdf',
            'file_type' => 'quote-summary',
            'size' => 30,
        ]);
        $this->assertSame(FileTypes::QUOTE_SUMMARY, $file->file_type);
        $this->assertStringStartsWith("custom/quotes/{$quote->id}/", $file->path);
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
        $quote = Quote::factory()->create(['address' => null]);
        $action = app(GeneratePdf::class);

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
        $quote = Quote::factory()->create();
        $upload = app(UploadQuoteFile::class);
        $image = $upload->handle(UploadedFile::fake()->image('boiler.png'), $quote, FileTypes::BOILER_LABEL);
        $image->update(['label' => '<script>attachment</script>.png']);
        $document = $upload->handle(UploadedFile::fake()->create('receipt.pdf', 10, 'application/pdf'), $quote, FileTypes::RECEIPT);
        $summary = $upload->handle(UploadedFile::fake()->create('old-summary.pdf', 10, 'application/pdf'), $quote, FileTypes::QUOTE_SUMMARY);
        $otherQuote = Quote::factory()->create();
        $otherImage = $upload->handle(UploadedFile::fake()->image('other.png'), $otherQuote, FileTypes::BOILER_LABEL);
        $imageSource = 'data:image/png;base64,' . base64_encode(Storage::disk()->get($image->path));
        $quote->load('files');

        app(GeneratePdf::class)->handle($quote);

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
        $quote = Quote::factory()->create();
        $image = app(UploadQuoteFile::class)->handle(UploadedFile::fake()->image('boiler.png'), $quote, FileTypes::BOILER_LABEL);
        Storage::disk()->delete($image->path);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not read quote attachment: ' . $image->label);

        try {
            app(GeneratePdf::class)->handle($quote);
        } finally {
            Http::assertNothingSent();
            $this->assertDatabaseMissing('files', ['quote_id' => $quote->id, 'file_type' => 'quote-summary']);
            Storage::disk()->assertEmpty();
        }
    }

    public function test_rendering_failure_does_not_create_a_file_or_database_record(): void
    {
        Storage::fake('local');
        Http::fake([self::ENDPOINT => Http::response('Rendering failed', 500)]);
        $quote = Quote::factory()->create();

        $this->expectException(CouldNotGeneratePdf::class);

        try {
            app(GeneratePdf::class)->handle($quote);
        } finally {
            $this->assertDatabaseMissing('files', ['quote_id' => $quote->id]);
            Storage::disk()->assertEmpty();
        }
    }

    public function test_storage_failure_does_not_create_a_database_record(): void
    {
        Http::fake([self::ENDPOINT => Http::response(self::PDF_CONTENT, 200)]);
        $quote = Quote::factory()->create();
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('put')->once()->with(Mockery::type('string'), self::PDF_CONTENT)->andReturnFalse();
        Storage::shouldReceive('disk')->once()->withNoArgs()->andReturn($disk);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not store the quote summary PDF.');

        try {
            app(GeneratePdf::class)->handle($quote);
        } finally {
            $this->assertDatabaseMissing('files', ['quote_id' => $quote->id]);
        }
    }

    public function test_record_creation_failure_removes_the_stored_pdf(): void
    {
        Storage::fake('local');
        Http::fake([self::ENDPOINT => Http::response(self::PDF_CONTENT, 200)]);
        $quote = Quote::factory()->create();
        File::creating(function (File $file): void {
            throw new RuntimeException('File record could not be saved.');
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File record could not be saved.');

        try {
            app(GeneratePdf::class)->handle($quote);
        } finally {
            $this->assertDatabaseMissing('files', ['quote_id' => $quote->id]);
            Storage::disk()->assertEmpty();
        }
    }
}
