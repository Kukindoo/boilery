<?php

namespace Tests\Integration\Actions\Files;

use App\Actions\Files\ChangeFileType;
use App\Enums\FileTypes;
use App\Enums\Permissions;
use App\Enums\Roles;
use App\Models\File;
use App\Models\Quote;
use App\Models\User;
use App\Models\Work;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
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
        $this->assertSame('receipt.pdf', $updated->label);
        $this->assertSame($file->original_name, $updated->original_name);
        $this->assertSame('receipt.pdf', $other->fresh()->label);
        $this->assertSame(FileTypes::RECEIPT, $other->fresh()->file_type);
        $this->assertEquals($file->attachments()->get()->toArray(), $updated->attachments()->get()->toArray());
        $this->assertSame($file->path, $updated->path);
        $activity = Activity::inLog('files')->where('event', 'action_completed')->sole();
        $this->assertTrue($activity->subject->is($file));
        $this->assertTrue($activity->causer->is($actor));
        $this->assertSame(ChangeFileType::class, $activity->getProperty('action'));
        $this->assertSame(FileTypes::RECEIPT->value, $activity->getProperty('previous_type'));
        $this->assertSame(FileTypes::WARRANTY->value, $activity->getProperty('file_type'));
    }

    #[DataProvider('ownedContexts')]
    public function test_admin_with_own_file_permission_can_change_an_owned_file(string $context): void
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
        $file = $this->attachment('quote', $actor)->load('issues');
        $otherQuote = Quote::factory()->forCustomer(User::factory())->create();
        $file->issues()->sync([$otherQuote->issue_id]);

        $this->expectException(AuthorizationException::class);

        try {
            app(ChangeFileType::class)->handle($file, FileTypes::WARRANTY);
        } finally {
            $this->assertSame(FileTypes::RECEIPT, $file->fresh()->file_type);
            $this->assertTrue($file->isAttachedTo($otherQuote));
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

    public function test_shared_file_type_changes_for_all_attachments_without_renaming(): void
    {
        $actor = $this->actor(Permissions::FILE_EDIT_ALL);
        $file = $this->attachment('quote', $actor);
        $file->update(['label' => 'Jan_Novak_42_kupni_smlouva.pdf']);
        $work = Work::factory()->create();
        $work->files()->attach($file);

        app(ChangeFileType::class)->handle($file, FileTypes::WARRANTY);

        $this->assertSame('Jan_Novak_42_kupni_smlouva.pdf', $file->issues()->firstOrFail()->files()->sole()->label);
        $this->assertSame('Jan_Novak_42_kupni_smlouva.pdf', $work->files()->sole()->label);
        $this->assertSame(FileTypes::WARRANTY, $file->issues()->firstOrFail()->files()->sole()->file_type);
        $this->assertSame(FileTypes::WARRANTY, $work->files()->sole()->file_type);
        $this->assertSame(2, $file->attachments()->count());
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
            $this->assertSame('receipt.pdf', $file->fresh()->label);
            $this->assertDatabaseMissing('activity_log', ['event' => 'action_completed']);
            $attempt = Activity::inLog('files')->where('event', 'action_attempted')->sole();
            $this->assertSame(ChangeFileType::class, $attempt->getProperty('action'));
        }
    }

    private function actor(Permissions $permission): User
    {
        $actor = User::factory()->create();
        $role = Role::findById(Roles::ADMIN->value, 'web');
        $role->syncPermissions([$permission]);
        $actor->assignRole($role);
        $this->actingAs($actor);

        return $actor;
    }

    private function attachment(string $context, User $owner): File
    {
        $file = File::factory()->create();
        $parent = match ($context) {
            'quote' => Quote::factory()->forCustomer($owner)->create(),
            'work' => Work::factory()->forCustomer($owner)->create(),
            default => null,
        };
        $parent?->files()->attach($file->id);

        return $file;
    }
}
