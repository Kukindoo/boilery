<?php

namespace Tests\Integration\Actions\Quotes;

use App\Actions\Quotes\CreateQuote;
use App\Dtos\Quotes\QuoteDto;
use App\Enums\BoilerManufacturers;
use App\Enums\FileTypes;
use App\Enums\QuoteStatus;
use App\Models\File;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\Integration\IntegrationTestCase;

class CreateQuoteTest extends IntegrationTestCase
{
    /** @return array<string, array{string, FileTypes, string}> */
    public static function attachmentTypes(): array
    {
        return [
            'label' => ['fileLabel', FileTypes::BOILER_LABEL, 'stitek'],
            'receipt' => ['fileReceipt', FileTypes::RECEIPT, 'kupni_smlouva'],
            'warranty' => ['fileWarrantyDocument', FileTypes::WARRANTY, 'zaruka'],
        ];
    }

    public function test_creates_public_quote_for_supplied_customer_without_attachments(): void
    {
        $form = $this->form();

        $quote = app(CreateQuote::class)->handle($form);

        $this->assertDatabaseHas('quotes', [
            'id' => $quote->id, 'issue_id' => $quote->issue_id,
            'first_name' => 'Jan', 'last_name' => 'Novak',
            'email' => 'jan@example.test', 'phone' => '+420123456789',
            'message' => 'No hot water', 'address' => null,
            'under_warranty' => false, 'boiler_manufacturer' => 'drazice',
            'boiler_serial_number' => null, 'boiler_type' => null,
        ]);
        $this->assertSame(QuoteStatus::NEW, $quote->fresh()->status);
        $this->assertDatabaseCount('files', 0);
    }

    #[DataProvider('attachmentTypes')]
    public function test_stores_attachment_with_correct_quote_and_type(string $field, FileTypes $type, string $labelType): void
    {
        $this->travelTo(Carbon::create(2026, 11, 8, 9, 42, 0, 'UTC'));
        Storage::fake(config('filesystems.default'));
        $customer = User::factory()->customer()->create(['name' => 'Jiří Novák-Svoboda', 'email' => 'jan@example.test']);
        $this->actingAs($customer);
        $form = $this->form();
        $form->underWarranty = true;
        $form->address = 'Prague';
        $form->boilerSerialNumber = 'SERIAL-1';
        $form->boilerType = 'OKCE 125';
        $fileUpload = UploadedFile::fake()->create('document.pdf', 10, 'application/pdf');

        $quote = app(CreateQuote::class)->handle($form, ...[$field => $fileUpload]);

        $file = $quote->files()->sole();
        $this->assertMatchesRegularExpression('/^2026-11-08_10-42_Jiri_Novak_Svoboda_' . $labelType . '_[a-z0-9]{4}\.pdf$/', $file->label);
        $this->assertSame($type, $file->file_type);
        $this->assertTrue($file->isAttachedTo($quote));
        $this->assertDatabaseHas('files', [
            'id' => $file->id,
            'label' => $file->label,
            'original_name' => 'document.pdf',
            'extension' => 'pdf',
            'mime_type' => 'application/pdf',
            'size' => 10240,
        ]);
        Storage::disk(config('filesystems.default'))->assertExists($file->path);
        $this->assertDatabaseHas('quotes', [
            'id' => $quote->id, 'issue_id' => $quote->issue_id, 'under_warranty' => true,
            'address' => 'Prague', 'boiler_serial_number' => 'SERIAL-1', 'boiler_type' => 'OKCE 125',
        ]);
    }

    public function test_upload_failure_rolls_back_quote_creation(): void
    {
        $form = $this->form();
        $upload = UploadedFile::fake()->create('document.pdf');
        $usersBefore = User::count();
        Storage::fake(config('filesystems.default'));
        $failure = new RuntimeException('Upload failed');
        File::creating(function () use ($failure): void {
            throw $failure;
        });

        try {
            app(CreateQuote::class)->handle($form, fileLabel: $upload);
            $this->fail('Upload failure should propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame($failure, $exception);
            $this->assertDatabaseCount('users', $usersBefore);
            $this->assertDatabaseCount('issues', 0);
            $this->assertDatabaseCount('quotes', 0);
            $this->assertDatabaseCount('files', 0);
            $this->assertSame([], Storage::disk()->allFiles());
        }
    }

    private function form(): QuoteDto
    {
        return new QuoteDto(
            firstName: 'Jan', lastName: 'Novak',
            email: 'jan@example.test', phone: '+420123456789',
            message: 'No hot water', boilerManufacturer: BoilerManufacturers::DRAZICE,
        );
    }
}
