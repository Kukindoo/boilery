<?php

namespace Tests\Feature\Actions\Files;

use App\Actions\Files\DeleteFile;
use App\Actions\Files\UploadQuoteFile;
use App\Enums\FileTypes;
use App\Enums\Permissions;
use App\Jobs\Files\DeleteStoredFile;
use App\Models\File;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\Feature\FeatureTestCase;

class DeleteFileTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['filesystems.default' => 'file-documents']);
    }

    /** @return array<string, array{Permissions, bool}> */
    public static function allowedDeletions(): array
    {
        return [
            'all permission on another customer file' => [Permissions::FILE_DELETE_ALL, false],
            'own permission on own file' => [Permissions::FILE_DELETE_OWN, true],
        ];
    }

    /** @return array<string, array{bool, ?Permissions, bool}> */
    public static function deniedDeletions(): array
    {
        return [
            'guest' => [false, null, false],
            'owner without permission' => [true, null, true],
            'own permission on another customer file' => [true, Permissions::FILE_DELETE_OWN, false],
        ];
    }

    #[DataProvider('allowedDeletions')]
    public function test_removes_only_the_authorized_file_and_its_database_record(Permissions $permission, bool $ownsQuote): void
    {
        Storage::fake('file-documents');
        Queue::fake([DeleteStoredFile::class]);
        $user = $this->operator($permission);
        $quote = Quote::factory()->forCustomer($ownsQuote ? $user : User::factory())->create();
        $file = $this->upload($quote);
        $otherFile = $this->upload($quote);
        $this->actingAs($user);

        app(DeleteFile::class)->handle($file);

        $this->assertModelMissing($file);
        $this->assertDatabaseMissing('fileables', ['file_id' => $file->id]);
        Storage::disk()->assertExists($file->path);
        Queue::assertPushed(DeleteStoredFile::class, fn (DeleteStoredFile $job): bool => $job->path === $file->path
            && $job->disk === 'file-documents' && $job->queue === 'default' && $job->afterCommit === true);
        (new DeleteStoredFile($file->path, 'file-documents'))->handle();
        Storage::disk()->assertMissing($file->path);
        $this->assertModelExists($otherFile);
        Storage::disk()->assertExists($otherFile->path);
    }

    #[DataProvider('deniedDeletions')]
    public function test_forbidden_deletion_preserves_the_stored_file_and_record(bool $authenticated, ?Permissions $permission, bool $ownsQuote): void
    {
        Storage::fake('file-documents');
        Queue::fake([DeleteStoredFile::class]);
        $user = $this->operator($permission);
        $quote = Quote::factory()->forCustomer($ownsQuote ? $user : User::factory())->create();
        $file = $this->upload($quote);

        if ($authenticated) {
            $this->actingAs($user);
        }

        $this->expectException(AuthorizationException::class);

        try {
            app(DeleteFile::class)->handle($file);
        } finally {
            $this->assertModelExists($file);
            Storage::disk()->assertExists($file->path);
            Queue::assertNothingPushed();
        }
    }

    public function test_worker_removes_storage_only_after_database_deletion_commits(): void
    {
        Storage::fake('file-documents');
        config(['queue.default' => 'database']);
        $file = $this->upload(Quote::factory()->forCustomer(User::factory())->create());
        $this->actingAs($this->operator(Permissions::FILE_DELETE_ALL));

        DB::transaction(function () use ($file): void {
            app(DeleteFile::class)->handle($file);
            $this->assertModelMissing($file);
            Storage::disk()->assertExists($file->path);
            $this->assertDatabaseCount('jobs', 0);
        });

        $this->assertDatabaseHas('jobs', ['queue' => 'default']);
        Storage::disk()->assertExists($file->path);
        $this->artisan('queue:work', ['connection' => 'database', '--queue' => 'default', '--once' => true, '--sleep' => 0])
            ->assertExitCode(0);
        Storage::disk()->assertMissing($file->path);
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_outer_rollback_preserves_the_file_attachment_and_storage_without_scheduling_cleanup(): void
    {
        Storage::fake('file-documents');
        config(['queue.default' => 'database']);
        $quote = Quote::factory()->forCustomer(User::factory())->create();
        $file = $this->upload($quote);
        $this->actingAs($this->operator(Permissions::FILE_DELETE_ALL));

        try {
            DB::transaction(function () use ($file): void {
                app(DeleteFile::class)->handle($file);
                throw new RuntimeException('Caller failed.');
            });
            $this->fail('The caller failure must propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Caller failed.', $exception->getMessage());
        }

        $this->assertModelExists($file);
        $this->assertTrue($quote->files()->whereKey($file->id)->exists());
        Storage::disk()->assertExists($file->path);
        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseMissing('activity_log', ['description' => 'File deleted']);
    }

    public function test_completion_log_failure_rolls_back_deletion_and_schedules_no_cleanup(): void
    {
        Storage::fake('file-documents');
        Queue::fake([DeleteStoredFile::class]);
        $quote = Quote::factory()->forCustomer(User::factory())->create();
        $file = $this->upload($quote);
        $this->actingAs($this->operator(Permissions::FILE_DELETE_ALL));
        Activity::creating(function (Activity $activity): void {
            if ($activity->description === 'File deleted') {
                throw new RuntimeException('Could not log deletion.');
            }
        });
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not log deletion.');

        try {
            app(DeleteFile::class)->handle($file);
        } finally {
            $this->assertModelExists($file);
            $this->assertTrue($quote->files()->whereKey($file->id)->exists());
            Storage::disk()->assertExists($file->path);
            Queue::assertNothingPushed();
        }
    }

    public function test_cancelled_record_deletion_does_not_schedule_storage_cleanup(): void
    {
        Storage::fake('file-documents');
        Queue::fake([DeleteStoredFile::class]);
        $file = $this->upload(Quote::factory()->forCustomer(User::factory())->create());
        $this->actingAs($this->operator(Permissions::FILE_DELETE_ALL));
        File::deleting(fn (File $file): bool => false);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not delete the file record.');

        try {
            app(DeleteFile::class)->handle($file);
        } finally {
            $this->assertModelExists($file);
            Storage::disk()->assertExists($file->path);
            Queue::assertNothingPushed();
        }
    }

    public function test_record_deletion_failure_preserves_the_stored_file_and_record(): void
    {
        Storage::fake('file-documents');
        $file = $this->upload(Quote::factory()->forCustomer(User::factory())->create());
        $this->actingAs($this->operator(Permissions::FILE_DELETE_ALL));
        File::deleting(function (File $file): void {
            throw new RuntimeException('File record could not be deleted.');
        });
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File record could not be deleted.');

        try {
            app(DeleteFile::class)->handle($file);
        } finally {
            $this->assertModelExists($file);
            Storage::disk()->assertExists($file->path);
        }
    }

    public function test_removes_the_record_when_the_stored_file_is_already_missing(): void
    {
        Storage::fake('file-documents');
        Queue::fake([DeleteStoredFile::class]);
        $file = $this->upload(Quote::factory()->forCustomer(User::factory())->create());
        Storage::disk()->delete($file->path);
        $this->actingAs($this->operator(Permissions::FILE_DELETE_ALL));

        app(DeleteFile::class)->handle($file);

        $this->assertModelMissing($file);
        Queue::assertPushed(DeleteStoredFile::class, 1);
        (new DeleteStoredFile($file->path, 'file-documents'))->handle();
        Storage::disk()->assertMissing($file->path);
    }

    private function upload(Quote $quote): File
    {
        return app(UploadQuoteFile::class)->handle(
            UploadedFile::fake()->create('receipt.pdf', 10, 'application/pdf'),
            $quote,
            FileTypes::RECEIPT,
        );
    }

    private function operator(?Permissions $permission): User
    {
        $user = User::factory()->create();

        if ($permission !== null) {
            $role = Role::create(['name' => 'delete-file-test', 'guard_name' => 'web']);
            $role->givePermissionTo($permission);
            $user->assignRole($role);
        }

        return $user;
    }
}
