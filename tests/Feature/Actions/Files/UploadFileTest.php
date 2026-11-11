<?php

namespace Tests\Feature\Actions\Files;

use App\Actions\Files\UploadFile;
use App\Enums\FileTypes;
use App\Enums\Permissions;
use App\Models\File;
use App\Models\FileAttachment;
use App\Models\Quote;
use App\Models\User;
use App\Models\Work;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\Feature\FeatureTestCase;

class UploadFileTest extends FeatureTestCase
{
    public static function origins(): array
    {
        return ['quote' => ['quote'], 'work' => ['work']];
    }

    public function test_label_is_generated_before_the_file_is_persisted(): void
    {
        Storage::fake();
        $this->actingAs(User::factory()->admin()->create());
        $quote = Quote::factory()->forCustomer(User::factory())->create();
        $labelAtCreation = null;
        File::creating(function (File $file) use (&$labelAtCreation): void {
            $labelAtCreation = $file->label;
        });

        $file = app(UploadFile::class)->handle(
            UploadedFile::fake()->image('receipt.jpg'), $quote, FileTypes::RECEIPT,
        );

        $this->assertNotEmpty($labelAtCreation);
        $this->assertSame($file->label, $labelAtCreation);
        $this->assertModelExists($file);
        $this->assertTrue($file->isAttachedTo($quote));
        Storage::disk()->assertExists($file->path);
    }

    #[DataProvider('origins')]
    public function test_issue_attachment_failure_rolls_back_the_record_and_storage(string $origin): void
    {
        Storage::fake();
        $user = User::factory()->admin()->create();
        $this->actingAs($user);
        $quote = Quote::factory()->forCustomer(User::factory())->create();
        $work = Work::factory()->for($quote->issue)->create();
        $created = 0;
        FileAttachment::creating(function () use (&$created): void {
            if (++$created === 1) {
                throw new RuntimeException('Cannot share attachment');
            }
        });

        try {
            app(UploadFile::class)->handle(UploadedFile::fake()->image('receipt.jpg'), $origin === 'quote' ? $quote : $work, FileTypes::RECEIPT);
            $this->fail('Sharing failure must propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Cannot share attachment', $exception->getMessage());
            $this->assertDatabaseCount('files', 0);
            $this->assertDatabaseCount('fileables', 0);
            Storage::disk()->assertEmpty();
        }
    }

    public function test_upload_rechecks_current_work_ownership_in_the_action(): void
    {
        Storage::fake();
        $user = User::factory()->create();
        $user->givePermissionTo([Permissions::WORK_EDIT_OWN, Permissions::FILE_CREATE_OWN]);
        $this->actingAs($user);
        $staleWork = Work::factory()->forCustomer($user)->create();
        $staleWork->issue->update(['user_id' => User::factory()->create()->id]);

        try {
            app(UploadFile::class)->handle(UploadedFile::fake()->image('receipt.jpg'), $staleWork, FileTypes::RECEIPT);
            $this->fail('The former owner cannot upload.');
        } catch (AuthorizationException) {
            $this->assertDatabaseCount('files', 0);
            $this->assertDatabaseCount('fileables', 0);
            Storage::disk()->assertEmpty();
        }
    }
}
