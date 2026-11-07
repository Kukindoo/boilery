<?php

namespace Tests\Feature\Jobs\Works;

use App\Actions\Works\QueuePdf;
use App\Enums\FileTypes;
use App\Enums\PdfGenerationStatus;
use App\Jobs\Works\GenerateServiceProtocolPdf;
use App\Models\User;
use App\Models\Work;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Spatie\LaravelPdf\Exceptions\CouldNotGeneratePdf;
use Tests\Feature\FeatureTestCase;

class GenerateServiceProtocolPdfTest extends FeatureTestCase
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

    public function test_job_renders_and_saves_the_service_protocol_and_marks_generation_completed(): void
    {
        Storage::fake('local');
        $work = $this->queuedWork();
        Http::fake([self::ENDPOINT => function (Request $request) use ($work) {
            $this->assertSame(PdfGenerationStatus::PROCESSING, $work->fresh()->pdf_generation_status);
            $this->assertStringContainsString('SERVISNÍ PROTOKOL', $request['html']);
            $this->assertStringContainsString(e($work->order_id), $request['html']);
            $this->assertStringNotContainsString('Shrnutí poptávky', $request['html']);

            return Http::response('%PDF-1.7 generated service_protocol', 200);
        }]);

        GenerateServiceProtocolPdf::dispatchSync($work->id, self::GENERATION_ID);
        GenerateServiceProtocolPdf::dispatchSync($work->id, self::GENERATION_ID);

        $this->assertSame(PdfGenerationStatus::COMPLETED, $work->fresh()->pdf_generation_status);
        $file = $work->files()->sole();
        $this->assertStringStartsWith('files/attachments/', $file->path);
        $this->assertSame(FileTypes::SERVICE_PROTOCOL, $file->file_type);
        Storage::disk()->assertExists($file->path);
        $this->assertSame('%PDF-1.7 generated service_protocol', Storage::disk()->get($file->path));
        Http::assertSentCount(1);
    }

    public function test_database_queue_worker_processes_a_requested_pdf(): void
    {
        Storage::fake('local');
        config(['queue.default' => 'database']);
        Http::fake([self::ENDPOINT => Http::response('%PDF-1.7 queued service_protocol', 200)]);
        $work = Work::factory()->create();
        $this->actingAs(User::factory()->admin()->create());

        app(QueuePdf::class)->handle($work);

        $this->assertSame(PdfGenerationStatus::QUEUED, $work->fresh()->pdf_generation_status);
        $this->assertFalse($work->hasServiceProtocol());
        $this->assertDatabaseHas('jobs', ['queue' => 'default']);

        $this->artisan('queue:work', ['connection' => 'database', '--queue' => 'default', '--once' => true, '--sleep' => 0])
            ->assertExitCode(0);

        $this->assertSame(PdfGenerationStatus::COMPLETED, $work->fresh()->pdf_generation_status);
        Storage::disk()->assertExists($work->files()->sole()->path);
        $this->assertDatabaseMissing('jobs', ['queue' => 'default']);
        Http::assertSentCount(1);
    }

    public function test_rendering_failure_marks_the_generation_failed_and_creates_no_service_protocol(): void
    {
        Storage::fake('local');
        $work = $this->queuedWork();
        Http::fake([self::ENDPOINT => Http::response('Rendering failed', 500)]);

        $this->expectException(CouldNotGeneratePdf::class);

        try {
            GenerateServiceProtocolPdf::dispatchSync($work->id, self::GENERATION_ID);
        } finally {
            $this->assertSame(PdfGenerationStatus::FAILED, $work->fresh()->pdf_generation_status);
            $this->assertFalse($work->hasServiceProtocol());
            Storage::disk()->assertEmpty();
        }
    }

    public function test_job_with_an_old_generation_id_cannot_render_or_fail_a_new_request(): void
    {
        $work = $this->queuedWork();
        $oldId = '00000000-0000-4000-8000-000000000002';

        GenerateServiceProtocolPdf::dispatchSync($work->id, $oldId);
        (new GenerateServiceProtocolPdf($work->id, $oldId))->failed(new RuntimeException('Old failure'));

        $this->assertSame(PdfGenerationStatus::QUEUED, $work->fresh()->pdf_generation_status);
        $this->assertFalse($work->hasServiceProtocol());
        Http::assertNothingSent();
    }

    public function test_job_reuses_a_service_protocol_saved_before_a_previous_attempt_stopped(): void
    {
        $work = $this->queuedWork();
        $work->files()->create([
            'original_name' => 'service_protocol.pdf', 'label' => 'service_protocol.pdf',
            'file_type' => FileTypes::SERVICE_PROTOCOL, 'extension' => 'pdf',
            'size' => 100, 'mime_type' => 'application/pdf', 'path' => 'service_protocol.pdf',
        ]);

        GenerateServiceProtocolPdf::dispatchSync($work->id, self::GENERATION_ID);
        (new GenerateServiceProtocolPdf($work->id, self::GENERATION_ID))->failed(new RuntimeException('Late failure'));

        $this->assertSame(PdfGenerationStatus::COMPLETED, $work->fresh()->pdf_generation_status);
        $this->assertSame(1, $work->files()->count());
        Http::assertNothingSent();
    }

    public function test_job_does_not_render_a_deleted_work(): void
    {
        $work = $this->queuedWork();
        $work->delete();

        GenerateServiceProtocolPdf::dispatchSync($work->id, self::GENERATION_ID);

        $this->assertFalse($work->hasServiceProtocol());
        Http::assertNothingSent();
    }

    private function queuedWork(): Work
    {
        return Work::factory()->create([
            'pdf_generation_status' => PdfGenerationStatus::QUEUED,
            'pdf_generation_id' => self::GENERATION_ID,
        ]);
    }
}
