<?php

namespace Tests\Integration\Actions;

use App\Actions\Quotes\UpdateQuote;
use App\Dtos\Quotes\QuoteDto;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ActionActivityRollbackTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate:fresh')->assertExitCode(0);
        $this->beforeApplicationDestroyed(function (): void {
            $this->artisan('db:wipe')->assertExitCode(0);
            RefreshDatabaseState::$migrated = false;
        });
    }

    public function test_failed_attempt_is_persisted_after_a_real_outermost_rollback(): void
    {
        // Do not wrap this test in RefreshDatabase's transaction: the audit must
        // survive a real root rollback, not just a rollback to a savepoint.
        $this->assertSame(0, DB::transactionLevel());
        $actor = User::factory()->admin()->create();
        $this->actingAs($actor);
        $quote = Quote::factory()->for(User::factory())->create();
        $before = $quote->fresh()->getRawOriginal();
        $failure = new RuntimeException('The success trace could not be stored.');
        Activity::creating(function (Activity $activity) use ($failure): void {
            if ($activity->description === 'Quote updated') {
                throw $failure;
            }
        });

        try {
            DB::transaction(function () use ($quote): void {
                app(UpdateQuote::class)->handle($quote, new QuoteDto(
                    firstName: 'Jan', lastName: 'Novák', email: 'jan@example.test',
                    phone: '123456789', message: 'Changed problem',
                ));
            });
            $this->fail('The original failure must propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame($failure, $exception);
        }

        $this->assertSame(0, DB::transactionLevel());
        $this->assertSame($before, $quote->fresh()->getRawOriginal());
        $attempt = Activity::inLog('quotes')->where('event', 'action_attempted')->sole();
        $this->assertSame(UpdateQuote::class, $attempt->getProperty('action'));
        $this->assertTrue($attempt->subject->is($quote));
        $this->assertTrue($attempt->causer->is($actor));
        $this->assertDatabaseMissing('activity_log', ['event' => 'action_completed', 'description' => 'Quote updated']);
    }
}
