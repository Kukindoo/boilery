<?php

namespace Tests\Feature\Actions\Files;

use App\Actions\Files\UploadFile;
use App\Enums\FileTypes;
use App\Enums\Permissions;
use App\Models\File;
use App\Models\FileAttachment;
use App\Models\Issue;
use App\Models\Quote;
use App\Models\User;
use App\Models\Work;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Tests\Feature\FeatureTestCase;

class UploadFileTest extends FeatureTestCase
{
    public static function origins(): array
    {
        return ['quote' => ['quote'], 'work' => ['work']];
    }

    public static function uploadOrigins(): array
    {
        return ['quote' => ['quote'], 'work' => ['work'], 'issue' => ['issue']];
    }

    #[DataProvider('uploadOrigins')]
    public function test_upload_is_logged_only_against_the_issue(string $origin): void
    {
        Storage::fake();
        $actor = User::factory()->admin()->create();
        $this->actingAs($actor);
        $parent = match ($origin) {
            'quote' => Quote::factory()->forCustomer(User::factory())->create(),
            'work' => Work::factory()->forCustomer(User::factory())->create(),
            'issue' => Issue::factory()->for(User::factory())->create(),
        };

        $file = app(UploadFile::class)->handle(UploadedFile::fake()->image('receipt.jpg'), $parent, FileTypes::RECEIPT);

        $issue = $parent instanceof Issue ? $parent : $parent->issue;
        $activity = Activity::forSubject($issue)->where('description', 'File uploaded')->sole();
        $this->assertTrue($activity->causer->is($actor));
        $this->assertSame('action_completed', $activity->event);
        $this->assertSame($file->id, $activity->getProperty('file_id'));
        $this->assertSame('receipt.jpg', $activity->getProperty('original_name'));
        $this->assertSame(FileTypes::RECEIPT->value, $activity->getProperty('file_type'));
        $this->assertSame(1, Activity::query()->where('event', 'action_completed')->where('properties->action', UploadFile::class)->count());
        $this->assertSame('issues', $activity->log_name);
    }

    public function test_issue_activity_failure_rolls_back_upload(): void
    {
        Storage::fake();
        $this->actingAs(User::factory()->admin()->create());
        $quote = Quote::factory()->forCustomer(User::factory())->create();
        Activity::creating(function (Activity $activity): void {
            if ($activity->description === 'File uploaded') {
                throw new RuntimeException('Cannot log issue upload');
            }
        });

        try {
            app(UploadFile::class)->handle(UploadedFile::fake()->image('receipt.jpg'), $quote, FileTypes::RECEIPT);
            $this->fail('Logging failure must propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Cannot log issue upload', $exception->getMessage());
            $this->assertDatabaseCount('files', 0);
            $this->assertSame(0, Activity::query()->where('event', 'action_completed')->where('properties->action', UploadFile::class)->count());
            Storage::disk()->assertEmpty();
        }
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
