<?php

namespace Tests\Feature;

use App\Models\User;
use Error;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Mockery;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ExceptionActivityTest extends FeatureTestCase
{
    public function test_request_exception_is_logged_with_causer_and_keeps_normal_reporting(): void
    {
        $actor = User::factory()->create();
        $this->actingAs($actor);
        $exception = new RuntimeException('Service unavailable', 123);
        Log::spy();
        Route::get('/test-exception-activity', function () use ($exception): never {
            throw $exception;
        });

        $this->getJson('/test-exception-activity')->assertInternalServerError();

        $activity = Activity::inLog('exceptions')->sole();
        $this->assertSame('exception_reported', $activity->event);
        $this->assertSame('Service unavailable', $activity->description);
        $this->assertSame(RuntimeException::class, $activity->getProperty('exception'));
        $this->assertSame(123, $activity->getProperty('code'));
        $this->assertSame($exception->getFile(), $activity->getProperty('file'));
        $this->assertSame($exception->getLine(), $activity->getProperty('line'));
        $this->assertTrue($activity->causer->is($actor));
        Log::shouldHaveReceived('error')->with('Service unavailable', Mockery::on(
            fn (array $context): bool => $context['exception'] === $exception,
        ))->once();
    }

    public function test_reported_php_error_is_logged_without_a_user(): void
    {
        report(new Error('Unexpected PHP error'));

        $activity = Activity::inLog('exceptions')->sole();
        $this->assertSame(Error::class, $activity->getProperty('exception'));
        $this->assertSame('Unexpected PHP error', $activity->description);
        $this->assertNull($activity->causer_id);
    }

    public function test_ignored_exceptions_do_not_create_activity(): void
    {
        report(new NotFoundHttpException('Missing page'));

        $this->assertSame(0, Activity::inLog('exceptions')->count());
    }

    public function test_activity_write_failure_does_not_interrupt_normal_exception_reporting(): void
    {
        $exception = new RuntimeException('Original failure');
        Log::spy();
        Activity::creating(function (Activity $activity): void {
            if ($activity->log_name === 'exceptions') {
                throw new RuntimeException('Activity storage unavailable');
            }
        });

        report($exception);

        Log::shouldHaveReceived('error')->with('Original failure', Mockery::on(
            fn (array $context): bool => $context['exception'] === $exception,
        ))->once();
        $this->assertSame(0, Activity::inLog('exceptions')->count());
    }
}
