<?php

namespace Tests\Feature\Files;

use App\Actions\Quotes\ForceDeleteQuote;
use App\Actions\Works\ForceDeleteWork;
use App\Enums\Permissions;
use App\Jobs\Files\DeleteStoredFile;
use App\Models\File;
use App\Models\Quote;
use App\Models\User;
use App\Models\Work;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\Feature\FeatureTestCase;

class ParentFileCleanupTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['filesystems.default' => 'documents']);
    }

    /** @return array<string, array{class-string<Quote>|class-string<Work>|class-string<User>}> */
    public static function parents(): array
    {
        return ['quote' => [Quote::class], 'work' => [Work::class], 'user' => [User::class]];
    }

    /** @return array<string, array{class-string<Quote>|class-string<Work>}> */
    public static function softDeletingParents(): array
    {
        return ['quote' => [Quote::class], 'work' => [Work::class]];
    }

    #[DataProvider('parents')]
    public function test_permanent_deletion_cleans_orphans_and_preserves_files_shared_with_an_archived_parent(string $type): void
    {
        Storage::fake('documents');
        Queue::fake([DeleteStoredFile::class]);
        $parent = $type::factory()->create();
        $orphan = $this->attachFile($parent);
        $shared = $this->attachFile($parent);
        $otherParent = Quote::factory()->create();
        $otherParent->files()->attach($shared->id);
        $otherParent->delete();

        $this->deleteParent($parent);

        $this->assertDatabaseMissing($parent->getTable(), ['id' => $parent->id]);
        $this->assertModelMissing($orphan);
        $this->assertDatabaseMissing('fileables', ['file_id' => $orphan->id]);
        $this->assertModelExists($shared);
        $this->assertTrue($shared->isAttachedTo($otherParent));
        $this->assertFalse($shared->isAttachedTo($parent));
        Storage::disk()->assertExists([$orphan->path, $shared->path]);
        Queue::assertPushed(DeleteStoredFile::class, fn (DeleteStoredFile $job): bool => $job->path === $orphan->path
            && $job->disk === 'documents' && $job->queue === 'default' && $job->afterCommit === true);
        Queue::assertPushed(DeleteStoredFile::class, 1);
        (new DeleteStoredFile($orphan->path, 'documents'))->handle();
        Storage::disk()->assertMissing($orphan->path);
        Storage::disk()->assertExists($shared->path);
    }

    #[DataProvider('softDeletingParents')]
    public function test_archiving_and_restoring_preserve_attachments_and_schedule_no_cleanup(string $type): void
    {
        Storage::fake('documents');
        Queue::fake([DeleteStoredFile::class]);
        $parent = $type::factory()->create();
        $file = $this->attachFile($parent);

        $parent->delete();

        $this->assertSoftDeleted($parent);
        $this->assertModelExists($file);
        $this->assertTrue($file->isAttachedTo($parent));
        $parent->restore();
        $this->assertTrue($parent->files()->sole()->is($file));
        Storage::disk()->assertExists($file->path);
        Queue::assertNothingPushed();
    }

    #[DataProvider('parents')]
    public function test_outer_rollback_restores_parent_links_and_file_records_without_scheduling_cleanup(string $type): void
    {
        Storage::fake('documents');
        config(['queue.default' => 'database']);
        $parent = $type::factory()->create();
        $file = $this->attachFile($parent);

        try {
            DB::transaction(function () use ($parent): void {
                $this->deleteParent($parent);
                $this->assertDatabaseCount('jobs', 0);
                throw new RuntimeException('Caller failed.');
            });
            $this->fail('The caller failure must propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Caller failed.', $exception->getMessage());
        }

        $this->assertDatabaseHas($parent->getTable(), ['id' => $parent->id]);
        $this->assertModelExists($file);
        $this->assertTrue($file->isAttachedTo($parent));
        Storage::disk()->assertExists($file->path);
        $this->assertDatabaseCount('jobs', 0);
    }

    #[DataProvider('parents')]
    public function test_orphan_deletion_failure_rolls_back_direct_parent_deletion_and_previously_deleted_files(string $type): void
    {
        Storage::fake('documents');
        config(['queue.default' => 'database']);
        $parent = $type::factory()->create();
        $first = $this->attachFile($parent);
        $second = $this->attachFile($parent);
        File::deleting(fn (File $file): bool => $file->id !== $second->id);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not delete the orphaned file record.');

        try {
            $this->deleteParent($parent);
        } finally {
            $this->assertDatabaseHas($parent->getTable(), ['id' => $parent->id]);
            $this->assertModelExists($first);
            $this->assertModelExists($second);
            $this->assertTrue($first->isAttachedTo($parent));
            $this->assertTrue($second->isAttachedTo($parent));
            Storage::disk()->assertExists([$first->path, $second->path]);
            $this->assertDatabaseCount('jobs', 0);
        }
    }

    #[DataProvider('softDeletingParents')]
    public function test_force_delete_action_commits_before_a_worker_removes_orphan_storage(string $type): void
    {
        Storage::fake('documents');
        config(['queue.default' => 'database']);
        $parent = $type::factory()->create();
        $file = $this->attachFile($parent);
        $parent->delete();
        $this->actingAs($this->operator());
        $action = $parent instanceof Quote ? ForceDeleteQuote::class : ForceDeleteWork::class;

        app($action)->handle($parent);

        $this->assertDatabaseMissing($parent->getTable(), ['id' => $parent->id]);
        $this->assertModelMissing($file);
        Storage::disk()->assertExists($file->path);
        $this->assertDatabaseHas('jobs', ['queue' => 'default']);
        $this->artisan('queue:work', ['connection' => 'database', '--queue' => 'default', '--once' => true, '--sleep' => 0])
            ->assertExitCode(0);
        Storage::disk()->assertMissing($file->path);
        $this->assertDatabaseCount('jobs', 0);
    }

    #[DataProvider('softDeletingParents')]
    public function test_action_log_failure_restores_parent_and_attachments_and_cancels_cleanup(string $type): void
    {
        Storage::fake('documents');
        config(['queue.default' => 'database']);
        $parent = $type::factory()->create();
        $file = $this->attachFile($parent);
        $parent->delete();
        $this->actingAs($this->operator());
        $action = $parent instanceof Quote ? ForceDeleteQuote::class : ForceDeleteWork::class;
        Activity::creating(function (Activity $activity): void {
            if (str_contains($activity->description, 'permanently deleted')) {
                throw new RuntimeException('Could not log parent deletion.');
            }
        });
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not log parent deletion.');

        try {
            app($action)->handle($parent);
        } finally {
            $this->assertDatabaseHas($parent->getTable(), ['id' => $parent->id]);
            $this->assertModelExists($file);
            $this->assertTrue($file->isAttachedTo($parent));
            Storage::disk()->assertExists($file->path);
            $this->assertDatabaseCount('jobs', 0);
        }
    }

    private function operator(): User
    {
        $role = Role::create(['name' => 'parent-file-cleanup', 'guard_name' => 'web']);
        $role->givePermissionTo([Permissions::QUOTE_FORCE_DELETE_ALL, Permissions::WORK_FORCE_DELETE_ALL]);

        return User::factory()->create()->assignRole($role);
    }

    private function attachFile(Quote|Work|User $parent): File
    {
        $file = $parent->files()->save(File::factory()->make());
        Storage::disk()->put($file->path, 'attachment contents');

        return $file;
    }

    private function deleteParent(Quote|Work|User $parent): void
    {
        if ($parent instanceof Quote || $parent instanceof Work) {
            $parent->forceDelete();
        } else {
            $parent->delete();
        }
    }
}
