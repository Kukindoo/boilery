<?php

namespace Tests\Integration\Actions\Quotes;

use App\Actions\Files\UploadQuoteFile;
use App\Actions\Quotes\CreateQuote;
use App\Enums\BoilerManufacturers;
use App\Enums\FileTypes;
use App\Enums\QuoteStatus;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\Integration\IntegrationTestCase;

class CreateQuoteTest extends IntegrationTestCase
{
    public static function attachmentTypes(): array
    {
        return [
            'label' => ['fileLabel', FileTypes::BOILER_LABEL],
            'receipt' => ['fileReceipt', FileTypes::RECEIPT],
            'warranty' => ['fileWarrantyDocument', FileTypes::WARRANTY],
        ];
    }

    public function test_creates_public_quote_for_supplied_customer_without_attachments(): void
    {
        $customer = User::factory()->customer()->create();
        $form = $this->form();
        $form->userId = User::factory()->create()->id;

        $quote = app(CreateQuote::class)->handle($form, $customer);

        $this->assertDatabaseHas('quotes', [
            'id' => $quote->id, 'user_id' => $customer->id,
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
    public function test_stores_attachment_with_correct_quote_and_type(string $field, FileTypes $type): void
    {
        Storage::fake(config('filesystems.default'));
        $customer = User::factory()->customer()->create();
        $form = $this->form();
        $form->boilerUnderWarranty = 'yes';
        $form->address = 'Prague';
        $form->boilerSerialNumber = 'SERIAL-1';
        $form->boilerType = 'OKCE 125';
        $form->{$field} = UploadedFile::fake()->create('document.pdf', 10, 'application/pdf');

        $quote = app(CreateQuote::class)->handle($form, $customer);

        $file = $quote->files()->sole();
        $this->assertSame($type, $file->file_type);
        Storage::disk(config('filesystems.default'))->assertExists($file->path);
        $this->assertDatabaseHas('quotes', [
            'id' => $quote->id, 'under_warranty' => true,
            'address' => 'Prague', 'boiler_serial_number' => 'SERIAL-1', 'boiler_type' => 'OKCE 125',
        ]);
    }

    public function test_upload_failure_rolls_back_quote_creation(): void
    {
        $customer = User::factory()->customer()->create();
        $form = $this->form();
        $form->fileLabel = UploadedFile::fake()->create('document.pdf');
        $this->mock(UploadQuoteFile::class)->shouldReceive('handle')->once()
            ->andThrow(new RuntimeException('Upload failed'));

        try {
            app(CreateQuote::class)->handle($form, $customer);
            $this->fail('Upload failure should propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Upload failed', $exception->getMessage());
            $this->assertDatabaseCount('quotes', 0);
            $this->assertDatabaseCount('files', 0);
        }
    }

    private function form(): object
    {
        return (object) [
            'firstName' => 'Jan', 'lastName' => 'Novak',
            'email' => 'jan@example.test', 'phone' => '+420123456789',
            'message' => 'No hot water', 'address' => null,
            'boilerUnderWarranty' => 'no', 'boilerManufacturer' => BoilerManufacturers::DRAZICE,
            'boilerSerialNumber' => null, 'boilerType' => null,
            'fileLabel' => null, 'fileReceipt' => null, 'fileWarrantyDocument' => null,
        ];
    }
}
