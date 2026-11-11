<?php

namespace Tests\Feature;

use App\Actions\Issues\CreateIssueQuote;
use App\Actions\Issues\CreateIssueWork;
use App\Enums\Permissions;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Throwable;

class IssueCreationActivityTest extends FeatureTestCase
{
    /** @return array<string, array{class-string<CreateIssueQuote>|class-string<CreateIssueWork>, string}> */
    public static function actions(): array
    {
        return [
            'quote' => [CreateIssueQuote::class, 'quotes'],
            'work' => [CreateIssueWork::class, 'works'],
        ];
    }

    /** @throws Throwable */
    #[DataProvider('actions')]
    public function test_denied_creation_logs_the_attempt_and_creates_no_record(string $action, string $log): void
    {
        $actor = User::factory()->create();
        $issue = Issue::factory()->create();
        $this->actingAs($actor);
        $caught = null;

        try {
            app($action)->handle($issue);
        } catch (AuthorizationException $exception) {
            $caught = $exception;
        }

        $this->assertInstanceOf(AuthorizationException::class, $caught);
        $attempt = Activity::inLog($log)->where('event', 'action_attempted')->sole();
        $this->assertSame($action, $attempt->getProperty('action'));
        $this->assertSame($issue->id, $attempt->getProperty('issue_id'));
        $this->assertSame(AuthorizationException::class, $attempt->getProperty('exception'));
        $this->assertTrue($attempt->causer->is($actor));
        $this->assertNull($attempt->subject_id);
        $this->assertDatabaseCount($log, 0);
        $this->assertSame(0, Activity::inLog($log)->where('event', 'action_completed')->count());
    }

    /** @throws Throwable */
    #[DataProvider('actions')]
    public function test_failed_success_logging_rolls_back_creation_and_preserves_attempt_after_outer_rollback(string $action, string $log): void
    {
        $actor = User::factory()->admin()->create();
        $actor->roles()->firstOrFail()->givePermissionTo([
            Permissions::QUOTE_CREATE_ALL->label(), Permissions::WORK_CREATE_ALL->label(),
        ]);
        $issue = Issue::factory()->create();
        $this->actingAs($actor);
        $failure = new RuntimeException('Could not log completion.');
        Activity::creating(function (Activity $activity) use ($action, $failure): void {
            if ($activity->event === 'action_completed' && $activity->getProperty('action') === $action) {
                throw $failure;
            }
        });
        $caught = null;

        try {
            DB::transaction(function () use ($action, $issue): void {
                app($action)->handle($issue);
            });
        } catch (RuntimeException $exception) {
            $caught = $exception;
        }

        $this->assertSame($failure, $caught);
        $attempt = Activity::inLog($log)->where('event', 'action_attempted')->sole();
        $this->assertSame($action, $attempt->getProperty('action'));
        $this->assertSame($issue->id, $attempt->getProperty('issue_id'));
        $this->assertSame(RuntimeException::class, $attempt->getProperty('exception'));
        $this->assertTrue($attempt->causer->is($actor));
        $this->assertDatabaseCount($log, 0);
        $this->assertSame(0, Activity::inLog($log)->where('event', 'action_completed')->count());
    }
}
