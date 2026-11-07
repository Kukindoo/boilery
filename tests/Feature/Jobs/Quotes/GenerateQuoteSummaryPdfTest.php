<?php

namespace Tests\Feature\Jobs\Quotes;

use App\Actions\Pdf\QueuePdf;
use App\Enums\FileTypes;
use App\Enums\PdfGenerationStatus;
use App\Jobs\Pdf\GeneratePdf;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Spatie\LaravelPdf\Exceptions\CouldNotGeneratePdf;
use Tests\Feature\FeatureTestCase;

class GenerateQuoteSummaryPdfTest extends FeatureTestCase
{
    private const GENERATION_ID = '00000000-0000-4000-8000-000000000001';

    private const ENDPOINT = 'https://api.cloudflare.com/client/v4/accounts/test-account/browser-rendering/pdf';

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

    public function test_job_renders_and_saves_the_summary_and_marks_generation_completed(): void
    {
        Storage::fake('local');
        $quote = $this->queuedQuote();
        Http::fake([self::ENDPOINT => function (Request $request) use ($quote) {
            $this->assertSame(PdfGenerationStatus::PROCESSING, $quote->fresh()->pdf_generation_status);

            return Http::response('%PDF-1.7 generated summary', 200);
        }]);

        GeneratePdf::dispatchSync('quote', $quote->id, self::GENERATION_ID);
        GeneratePdf::dispatchSync('quote', $quote->id, self::GENERATION_ID);

        $this->assertSame(PdfGenerationStatus::COMPLETED, $quote->fresh()->pdf_generation_status);
        $file = $quote->files()->sole();
        $this->assertStringStartsWith('files/attachments/', $file->path);
        $this->assertSame(FileTypes::QUOTE_SUMMARY, $file->file_type);
        Storage::disk()->assertExists($file->path);
        $this->assertSame('%PDF-1.7 generated summary', Storage::disk()->get($file->path));
        Http::assertSentCount(1);
    }

    public function test_database_queue_worker_processes_a_requested_pdf(): void
    {
        Storage::fake('local');
        config(['queue.default' => 'database']);
        Http::fake([self::ENDPOINT => Http::response('%PDF-1.7 queued summary', 200)]);
        $quote = Quote::factory()->for(User::factory())->create();
        $this->actingAs(User::factory()->admin()->create());

        app(QueuePdf::class)->handle($quote);

        $this->assertSame(PdfGenerationStatus::QUEUED, $quote->fresh()->pdf_generation_status);
        $this->assertFalse($quote->hasQuoteSummary());
        $this->assertDatabaseHas('jobs', ['queue' => 'default']);

        $this->artisan('queue:work', ['connection' => 'database', '--queue' => 'default', '--once' => true, '--sleep' => 0])
            ->assertExitCode(0);

        $this->assertSame(PdfGenerationStatus::COMPLETED, $quote->fresh()->pdf_generation_status);
        Storage::disk()->assertExists($quote->files()->sole()->path);
        $this->assertDatabaseMissing('jobs', ['queue' => 'default']);
        Http::assertSentCount(1);
    }

    public function test_rendering_failure_marks_the_generation_failed_and_creates_no_summary(): void
    {
        Storage::fake('local');
        $quote = $this->queuedQuote();
        Http::fake([self::ENDPOINT => Http::response('Rendering failed', 500)]);

        $this->expectException(CouldNotGeneratePdf::class);

        try {
            GeneratePdf::dispatchSync('quote', $quote->id, self::GENERATION_ID);
        } finally {
            $this->assertSame(PdfGenerationStatus::FAILED, $quote->fresh()->pdf_generation_status);
            $this->assertFalse($quote->hasQuoteSummary());
            Storage::disk()->assertEmpty();
        }
    }

    public function test_job_with_an_old_generation_id_cannot_render_or_fail_a_new_request(): void
    {
        $quote = $this->queuedQuote();
        $oldId = '00000000-0000-4000-8000-000000000002';

        GeneratePdf::dispatchSync('quote', $quote->id, $oldId);
        (new GeneratePdf('quote', $quote->id, $oldId))->failed(new RuntimeException('Old failure'));

        $this->assertSame(PdfGenerationStatus::QUEUED, $quote->fresh()->pdf_generation_status);
        $this->assertFalse($quote->hasQuoteSummary());
        Http::assertNothingSent();
    }

    public function test_job_reuses_a_summary_saved_before_a_previous_attempt_stopped(): void
    {
        $quote = $this->queuedQuote();
        $quote->files()->create([
            'original_name' => 'summary.pdf', 'label' => 'summary.pdf',
            'file_type' => FileTypes::QUOTE_SUMMARY, 'extension' => 'pdf',
            'size' => 100, 'mime_type' => 'application/pdf', 'path' => 'summary.pdf',
        ]);

        GeneratePdf::dispatchSync('quote', $quote->id, self::GENERATION_ID);
        (new GeneratePdf('quote', $quote->id, self::GENERATION_ID))->failed(new RuntimeException('Late failure'));

        $this->assertSame(PdfGenerationStatus::COMPLETED, $quote->fresh()->pdf_generation_status);
        $this->assertSame(1, $quote->files()->count());
        Http::assertNothingSent();
    }

    public function test_job_does_not_render_a_deleted_quote(): void
    {
        $quote = $this->queuedQuote();
        $quote->delete();

        GeneratePdf::dispatchSync('quote', $quote->id, self::GENERATION_ID);

        $this->assertFalse($quote->hasQuoteSummary());
        Http::assertNothingSent();
    }

    private function queuedQuote(): Quote
    {
        return Quote::factory()->for(User::factory())->create([
            'pdf_generation_status' => PdfGenerationStatus::QUEUED,
            'pdf_generation_id' => self::GENERATION_ID,
        ]);
    }
}
