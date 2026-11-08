<?php

namespace Tests\Feature\Actions\Works;

use App\Actions\Works\GenerateServiceProtocolPdf;
use App\Enums\FileTypes;
use App\Models\File;
use App\Models\Quote;
use App\Models\User;
use App\Models\Work;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;
use Spatie\LaravelPdf\Exceptions\CouldNotGeneratePdf;
use Tests\Feature\FeatureTestCase;

class GenerateServiceProtocolPdfTest extends FeatureTestCase
{
    private const ENDPOINT = 'https://api.cloudflare.com/client/v4/accounts/test-account/browser-rendering/pdf';

    private const PDF_CONTENT = "%PDF-1.7\nservice protocol\xC3\xA9\n%%EOF";

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::create(2026, 11, 8, 9, 42, 0, 'UTC'));

        config([
            'filesystems.default' => 'local',
            'laravel-pdf.driver' => 'cloudflare',
            'laravel-pdf.cloudflare.account_id' => 'test-account',
            'laravel-pdf.cloudflare.api_token' => 'test-token',
            'laravel-pdf.cache.automatic' => false,
        ]);
        Http::preventStrayRequests();
    }

    #[TestWith([true])]
    #[TestWith([false])]
    public function test_preview_and_generated_pdf_use_the_same_current_customer_details(bool $linkedQuote): void
    {
        Storage::fake('local');
        Http::fake([self::ENDPOINT => Http::response(self::PDF_CONTENT, 200)]);
        $customer = User::factory()->customer()->create([
            'name' => 'Current Customer', 'phone' => '+420111222333', 'address' => 'Current address 42',
        ]);
        $quote = $linkedQuote ? Quote::factory()->for($customer)->create([
            'phone' => '+420999888777', 'address' => 'Old quote address 99',
        ]) : null;
        $work = Work::factory()->for($customer)->create(['quote_id' => $quote?->id]);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('works.show', $work))->assertOk();
        $preview = $response->viewData('pdfContent');
        app(GenerateServiceProtocolPdf::class)->handle($work);

        $this->assertStringContainsString('Current Customer', $preview);
        $this->assertStringContainsString('+420111222333', $preview);
        $this->assertStringContainsString('Current address 42', $preview);
        $this->assertStringNotContainsString('+420999888777', $preview);
        $this->assertStringNotContainsString('Old quote address 99', $preview);
        Http::assertSent(fn (Request $request): bool => $request['html'] === $preview);
    }

    public function test_saves_the_service_protocol_with_customer_details_and_file_metadata(): void
    {
        Storage::fake('work-documents');
        config([
            'filesystems.default' => 'work-documents',
            'app.files.attachments_save_directory' => 'custom/protocols',
        ]);
        Http::fake([self::ENDPOINT => Http::response(self::PDF_CONTENT, 200)]);
        $customer = User::factory()->create([
            'name' => 'Jiří Novák',
            'address' => 'Pražská 123',
            'phone' => '+420123456789',
        ]);
        $work = Work::factory()->for($customer, 'user')->create([
            'order_id' => 'SERVIS-42',
            'reported_problem' => '<script>alert("test")</script>',
        ]);

        $labelAtCreation = null;
        File::creating(function (File $file) use (&$labelAtCreation): void {
            $labelAtCreation = $file->label;
        });

        $file = app(GenerateServiceProtocolPdf::class)->handle($work);

        $this->assertNotEmpty($labelAtCreation);
        $this->assertSame($file->label, $labelAtCreation);
        $this->assertMatchesRegularExpression('/^2026-11-08_10-42_Jiri_Novak_servisni_protokol_[a-z0-9]{4}\.pdf$/', $file->label);
        $this->assertModelExists($file);
        $this->assertTrue($file->isAttachedTo($work));
        $this->assertTrue($work->files()->sole()->is($file));
        $this->assertDatabaseHas('files', [
            'id' => $file->id,
            'original_name' => $file->label,
            'label' => $file->label,
            'extension' => 'pdf',
            'mime_type' => 'application/pdf',
            'file_type' => FileTypes::SERVICE_PROTOCOL->value,
            'size' => mb_strlen(self::PDF_CONTENT, '8bit'),
        ]);
        $this->assertSame(FileTypes::SERVICE_PROTOCOL, $file->file_type);
        $this->assertStringStartsWith('custom/protocols/', $file->path);
        Storage::disk()->assertExists($file->path);
        $this->assertSame(self::PDF_CONTENT, Storage::disk()->get($file->path));
        Http::assertSent(fn (Request $request): bool => $request->url() === self::ENDPOINT
            && $request['pdfOptions']['format'] === 'a4'
            && str_contains($request['html'], 'SERVISNÍ PROTOKOL')
            && str_contains($request['html'], 'SERVIS-42')
            && str_contains($request['html'], 'Jiří Novák')
            && str_contains($request['html'], 'Pražská 123')
            && str_contains($request['html'], '+420123456789')
            && str_contains($request['html'], '&lt;script&gt;')
            && ! str_contains($request['html'], '<script>'));
    }

    public function test_rendering_failure_does_not_create_a_file_or_database_record(): void
    {
        Storage::fake('local');
        Http::fake([self::ENDPOINT => Http::response('Rendering failed', 500)]);
        $work = Work::factory()->create();

        $this->expectException(CouldNotGeneratePdf::class);

        try {
            app(GenerateServiceProtocolPdf::class)->handle($work);
        } finally {
            $this->assertTrue($work->files()->doesntExist());
            Storage::disk()->assertEmpty();
        }
    }

    public function test_storage_failure_does_not_create_a_database_record(): void
    {
        Http::fake([self::ENDPOINT => Http::response(self::PDF_CONTENT, 200)]);
        $work = Work::factory()->create();
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('put')->once()->with(Mockery::type('string'), self::PDF_CONTENT)->andReturnFalse();
        Storage::shouldReceive('disk')->once()->withNoArgs()->andReturn($disk);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not store the service protocol PDF.');

        try {
            app(GenerateServiceProtocolPdf::class)->handle($work);
        } finally {
            $this->assertTrue($work->files()->doesntExist());
        }
    }

    public function test_attachment_link_failure_rolls_back_the_record_and_removes_the_pdf(): void
    {
        Storage::fake('local');
        Http::fake([self::ENDPOINT => Http::response(self::PDF_CONTENT, 200)]);
        $work = Work::factory()->create();
        \App\Models\FileAttachment::creating(function (): void {
            throw new RuntimeException('Cannot attach PDF');
        });

        try {
            app(GenerateServiceProtocolPdf::class)->handle($work);
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
        $work = Work::factory()->create();
        File::creating(function (File $file): void {
            throw new RuntimeException('File record could not be saved.');
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File record could not be saved.');

        try {
            app(GenerateServiceProtocolPdf::class)->handle($work);
        } finally {
            $this->assertTrue($work->files()->doesntExist());
            Storage::disk()->assertEmpty();
        }
    }
}
