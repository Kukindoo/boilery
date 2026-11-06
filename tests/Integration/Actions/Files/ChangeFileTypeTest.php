<?php

namespace Tests\Integration\Actions\Files;

use App\Actions\Files\ChangeFileType;
use App\Enums\FileTypes;
use App\Enums\Permissions;
use App\Models\File;
use App\Models\Quote;
use App\Models\User;
use App\Models\Work;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Tests\Integration\IntegrationTestCase;

class ChangeFileTypeTest extends IntegrationTestCase
{
    public static function contexts(): array
    {
        return ['quote' => ['quote'], 'work' => ['work'], 'unattached' => ['none']];
    }

    public static function ownedContexts(): array
    {
        return ['quote' => ['quote'], 'work' => ['work']];
    }

    #[DataProvider('contexts')]
    public function test_changes_any_file_type_and_records_the_actor_and_previous_type(string $context): void
    {
        $actor = $this->actor(Permissions::FILE_EDIT_ALL);
        $file = $this->attachment($context, $actor);
        $other = $this->attachment($context, $actor);

        $updated = app(ChangeFileType::class)->handle($file, FileTypes::WARRANTY);

        $this->assertTrue($updated->is($file));
        $this->assertSame(FileTypes::WARRANTY, $updated->file_type);
        $this->assertSame(FileTypes::RECEIPT, $other->fresh()->file_type);
        $this->assertSame($file->quote_id, $updated->quote_id);
        $this->assertSame($file->work_id, $updated->work_id);
        $this->assertSame($file->path, $updated->path);
        $activity = Activity::inLog('files')->where('event', 'action_completed')->sole();
        $this->assertTrue($activity->subject->is($file));
        $this->assertTrue($activity->causer->is($actor));
        $this->assertSame(ChangeFileType::class, $activity->getProperty('action'));
        $this->assertSame(FileTypes::RECEIPT->value, $activity->getProperty('previous_type'));
        $this->assertSame(FileTypes::WARRANTY->value, $activity->getProperty('file_type'));
    }

    #[DataProvider('ownedContexts')]
    public function test_own_file_permission_is_sufficient_for_the_generic_action(string $context): void
    {
        $actor = $this->actor(Permissions::FILE_EDIT_OWN);
        $file = $this->attachment($context, $actor);

        app(ChangeFileType::class)->handle($file, FileTypes::WARRANTY);

        $this->assertSame(FileTypes::WARRANTY, $file->fresh()->file_type);
    }

    public function test_denies_a_caller_without_file_permission_and_records_an_attempt(): void
    {
        $actor = User::factory()->create();
        $this->actingAs($actor);
        $file = $this->attachment('quote', $actor);

        try {
            app(ChangeFileType::class)->handle($file, FileTypes::WARRANTY);
            $this->fail('Changing the type must be forbidden.');
        } catch (AuthorizationException) {
            $this->assertSame(FileTypes::RECEIPT, $file->fresh()->file_type);
            $this->assertDatabaseMissing('activity_log', ['event' => 'action_completed']);
            $attempt = Activity::inLog('files')->where('event', 'action_attempted')->sole();
            $this->assertSame(ChangeFileType::class, $attempt->getProperty('action'));
        }
    }

    #[DataProvider('ownedContexts')]
    public function test_denies_another_owners_file(string $context): void
    {
        $this->actor(Permissions::FILE_EDIT_OWN);
        $file = $this->attachment($context, User::factory()->create());

        $this->expectException(AuthorizationException::class);

        app(ChangeFileType::class)->handle($file, FileTypes::WARRANTY);
    }

    public function test_uses_the_current_file_and_discards_stale_loaded_relations(): void
    {
        $actor = $this->actor(Permissions::FILE_EDIT_OWN);
        $file = $this->attachment('quote', $actor)->load('quote');
        $otherQuote = Quote::factory()->for(User::factory())->create();
        File::whereKey($file->id)->update(['quote_id' => $otherQuote->id]);

        $this->expectException(AuthorizationException::class);

        try {
            app(ChangeFileType::class)->handle($file, FileTypes::WARRANTY);
        } finally {
            $this->assertSame(FileTypes::RECEIPT, $file->fresh()->file_type);
            $this->assertSame($otherQuote->id, $file->fresh()->quote_id);
        }
    }

    public function test_rejects_a_deleted_file(): void
    {
        $actor = $this->actor(Permissions::FILE_EDIT_ALL);
        $file = $this->attachment('none', $actor);
        File::whereKey($file->id)->delete();

        $this->expectException(ModelNotFoundException::class);

        app(ChangeFileType::class)->handle($file, FileTypes::WARRANTY);
    }

    public function test_success_log_failure_rolls_back_the_type_change_and_preserves_an_attempt(): void
    {
        $actor = $this->actor(Permissions::FILE_EDIT_ALL);
        $file = $this->attachment('none', $actor);
        $failure = new RuntimeException('Could not store completion trace.');
        Activity::creating(function (Activity $activity) use ($failure): void {
            if ($activity->event === 'action_completed') {
                throw $failure;
            }
        });

        try {
            app(ChangeFileType::class)->handle($file, FileTypes::WARRANTY);
            $this->fail('The logging failure must propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame($failure, $exception);
            $this->assertSame(FileTypes::RECEIPT, $file->fresh()->file_type);
            $this->assertDatabaseMissing('activity_log', ['event' => 'action_completed']);
            $attempt = Activity::inLog('files')->where('event', 'action_attempted')->sole();
            $this->assertSame(ChangeFileType::class, $attempt->getProperty('action'));
        }
    }

    private function actor(Permissions $permission): User
    {
        $actor = User::factory()->create();
        $actor->givePermissionTo($permission);
        $this->actingAs($actor);

        return $actor;
    }

    private function attachment(string $context, User $owner): File
    {
        return File::create([
            'path' => 'receipt.pdf', 'label' => 'receipt.pdf', 'original_name' => 'receipt.pdf',
            'extension' => 'pdf', 'mime_type' => 'application/pdf', 'size' => 13,
            'file_type' => FileTypes::RECEIPT,
            'quote_id' => $context === 'quote' ? Quote::factory()->for($owner)->create()->id : null,
            'work_id' => $context === 'work' ? Work::factory()->for($owner)->create()->id : null,
        ]);
    }
}
