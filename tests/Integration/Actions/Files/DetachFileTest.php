<?php

namespace Tests\Integration\Actions\Files;

use App\Actions\Files\DetachFile;
use App\Enums\Permissions;
use App\Models\File;
use App\Models\Quote;
use App\Models\User;
use App\Models\Work;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Tests\Integration\IntegrationTestCase;

class DetachFileTest extends IntegrationTestCase
{
    public static function remainingParents(): array
    {
        return ['quote' => [Quote::class], 'work' => [Work::class], 'user' => [User::class]];
    }

    #[DataProvider('remainingParents')]
    public function test_detaching_from_an_owned_parent_preserves_other_links_and_storage(string $model): void
    {
        Storage::fake();
        $owner = User::factory()->create();
        $owner->givePermissionTo([Permissions::QUOTE_VIEW_OWN, Permissions::FILE_DELETE_OWN]);
        $this->actingAs($owner);
        $quote = Quote::factory()->forCustomer($owner)->create();
        $other = match ($model) {
            User::class => User::factory()->create(),
            Quote::class => Quote::factory()->forCustomer(User::factory())->create(),
            Work::class => Work::factory()->forCustomer(User::factory())->create(),
        };
        $file = File::factory()->create();
        Storage::put($file->path, 'shared content');
        $quote->files()->attach($file->id);
        $other->files()->attach($file->id);
        $this->assertFalse($owner->can('delete', $file));

        app(DetachFile::class)->handle($quote, $file);

        $this->assertFalse($file->isAttachedTo($quote));
        $this->assertTrue($file->isAttachedTo($other));
        $this->assertModelExists($file);
        Storage::disk()->assertExists($file->path);
        $activity = Activity::inLog('files')->where('description', 'File detached')->sole();
        $this->assertSame($quote->id, $activity->getProperty('fileable_id'));
    }

    public function test_last_detachment_deletes_the_record_and_queue_worker_removes_storage(): void
    {
        Storage::fake();
        config(['queue.default' => 'database']);
        $owner = User::factory()->create();
        $owner->givePermissionTo([Permissions::QUOTE_VIEW_OWN, Permissions::FILE_DELETE_OWN]);
        $this->actingAs($owner);
        $quote = Quote::factory()->forCustomer($owner)->create();
        $file = $quote->files()->save(File::factory()->make());
        Storage::put($file->path, 'content');

        app(DetachFile::class)->handle($quote, $file);

        $this->assertModelMissing($file);
        $this->assertDatabaseMissing('fileables', ['file_id' => $file->id]);
        $this->assertDatabaseHas('jobs', ['queue' => 'default']);
        Storage::disk()->assertExists($file->path);
        $this->artisan('queue:work', ['connection' => 'database', '--once' => true, '--sleep' => 0])->assertExitCode(0);
        Storage::disk()->assertMissing($file->path);
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_caller_rollback_preserves_the_last_attachment_and_schedules_no_cleanup(): void
    {
        Storage::fake();
        config(['queue.default' => 'database']);
        $owner = User::factory()->create();
        $owner->givePermissionTo([Permissions::QUOTE_VIEW_OWN, Permissions::FILE_DELETE_OWN]);
        $this->actingAs($owner);
        $quote = Quote::factory()->forCustomer($owner)->create();
        $file = $quote->files()->save(File::factory()->make());
        Storage::put($file->path, 'content');

        try {
            DB::transaction(function () use ($quote, $file): void {
                app(DetachFile::class)->handle($quote, $file);
                throw new RuntimeException('Caller failure');
            });
            $this->fail('The caller failure must propagate.');
        } catch (RuntimeException) {
            $this->assertModelExists($file);
            $this->assertTrue($file->isAttachedTo($quote));
            Storage::disk()->assertExists($file->path);
            $this->assertDatabaseCount('jobs', 0);
        }
    }

    public function test_parent_view_permission_does_not_grant_detachment_permission(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permissions::QUOTE_VIEW_OWN);
        $this->actingAs($user);
        $quote = Quote::factory()->forCustomer($user)->create();
        $file = $quote->files()->save(File::factory()->make());
        $this->expectException(AuthorizationException::class);

        try {
            app(DetachFile::class)->handle($quote, $file);
        } finally {
            $this->assertTrue($file->isAttachedTo($quote));
        }
    }

    public function test_activity_failure_rolls_back_detachment(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo([Permissions::QUOTE_VIEW_OWN, Permissions::FILE_DELETE_OWN]);
        $this->actingAs($user);
        $quote = Quote::factory()->forCustomer($user)->create();
        $file = $quote->files()->save(File::factory()->make());
        Activity::creating(function (Activity $activity): void {
            if ($activity->description === 'File detached') {
                throw new RuntimeException('Activity failed');
            }
        });

        try {
            app(DetachFile::class)->handle($quote, $file);
            $this->fail('Activity failure must propagate.');
        } catch (RuntimeException) {
            $this->assertTrue($file->isAttachedTo($quote));
            $this->assertSame(1, Activity::where('event', 'action_attempted')->count());
        }
    }
}
