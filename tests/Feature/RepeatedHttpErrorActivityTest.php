<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Mockery;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;

class RepeatedHttpErrorActivityTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->freezeTime();
        config(['activitylog.repeated_http_errors.threshold' => 3]);
        Route::get('/test-http-error/{status}', function (int $status): never {
            abort($status);
        });
    }

    #[TestWith([403])]
    #[TestWith([404])]
    public function test_logs_only_at_threshold_and_suppresses_entries_during_cooldown(int $status): void
    {
        for ($i = 0; $i < 2; $i++) {
            $this->getJson('/test-http-error/' . $status)->assertStatus($status);
        }
        $this->assertSame(0, Activity::inLog('exceptions')->count());

        $this->getJson('/test-http-error/' . $status . '?secret=hidden')->assertStatus($status);

        $activity = Activity::inLog('exceptions')->sole();
        $this->assertSame('repeated_http_error', $activity->event);
        $this->assertSame($status, $activity->getProperty('status'));
        $this->assertSame(3, $activity->getProperty('count'));
        $this->assertSame(300, $activity->getProperty('window_seconds'));
        $this->assertSame('test-http-error/' . $status, $activity->getProperty('path'));
        $this->assertSame('GET', $activity->getProperty('method'));
        $this->assertSame('127.0.0.1', $activity->getProperty('ip'));
        $this->assertNull($activity->causer_id);

        for ($i = 0; $i < 4; $i++) {
            $this->getJson('/test-http-error/' . $status)->assertStatus($status);
        }
        $this->assertSame(1, Activity::inLog('exceptions')->count());

        $this->travel(301)->seconds();
        for ($i = 0; $i < 3; $i++) {
            $this->getJson('/test-http-error/' . $status)->assertStatus($status);
        }
        $this->assertSame(2, Activity::inLog('exceptions')->count());
    }

    public function test_expired_counts_do_not_contribute_to_threshold(): void
    {
        $this->getJson('/test-http-error/404')->assertNotFound();
        $this->getJson('/test-http-error/404')->assertNotFound();
        $this->travel(301)->seconds();

        $this->getJson('/test-http-error/404')->assertNotFound();

        $this->assertSame(0, Activity::inLog('exceptions')->count());
    }

    public function test_missing_routes_are_logged_on_the_twentieth_response(): void
    {
        config(['activitylog.repeated_http_errors.threshold' => 20]);
        for ($i = 0; $i < 19; $i++) {
            $this->getJson('/missing-page-' . $i)->assertNotFound();
        }
        $this->assertSame(0, Activity::inLog('exceptions')->count());

        $this->getJson('/another-missing-page')->assertNotFound();

        $activity = Activity::inLog('exceptions')->sole();
        $this->assertSame(20, $activity->getProperty('count'));
        $this->assertSame('another-missing-page', $activity->getProperty('path'));
    }

    public function test_cooldown_outlasts_a_shorter_counting_window(): void
    {
        config([
            'activitylog.repeated_http_errors.threshold' => 1,
            'activitylog.repeated_http_errors.window_seconds' => 60,
        ]);
        $this->getJson('/test-http-error/404')->assertNotFound();
        $this->travel(61)->seconds();

        $this->getJson('/test-http-error/404')->assertNotFound();

        $this->assertSame(1, Activity::inLog('exceptions')->count());

        $this->travel(240)->seconds();
        $this->getJson('/test-http-error/404')->assertNotFound();

        $this->assertSame(2, Activity::inLog('exceptions')->count());
    }

    public function test_statuses_and_guest_ips_are_counted_separately(): void
    {
        $this->getJson('/test-http-error/403')->assertForbidden();
        $this->getJson('/test-http-error/403')->assertForbidden();
        $this->getJson('/test-http-error/404')->assertNotFound();
        $this->getJson('/test-http-error/404')->assertNotFound();

        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.1'])
            ->getJson('/test-http-error/404')->assertNotFound();
        $this->assertSame(0, Activity::inLog('exceptions')->count());

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->getJson('/test-http-error/404')->assertNotFound();

        $this->assertSame(404, Activity::inLog('exceptions')->sole()->getProperty('status'));
    }

    public function test_users_on_the_same_ip_have_separate_counts_and_user_counts_span_ips(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        $this->actingAs($first);
        $this->getJson('/test-http-error/403')->assertForbidden();
        $this->getJson('/test-http-error/403')->assertForbidden();
        $this->actingAs($second)->getJson('/test-http-error/403')->assertForbidden();
        $this->assertSame(0, Activity::inLog('exceptions')->count());

        $this->actingAs($first)->withServerVariables(['REMOTE_ADDR' => '192.0.2.2'])
            ->getJson('/test-http-error/403')->assertForbidden();

        $this->assertTrue(Activity::inLog('exceptions')->sole()->causer->is($first));
    }

    public function test_logging_failure_preserves_the_http_response(): void
    {
        config(['activitylog.repeated_http_errors.threshold' => 1]);
        Log::spy();
        Activity::creating(function (Activity $activity): void {
            if ($activity->event === 'repeated_http_error') {
                throw new RuntimeException('Activity storage unavailable');
            }
        });

        $this->getJson('/test-http-error/403')->assertForbidden();

        $this->assertSame(0, Activity::inLog('exceptions')->count());
        Log::shouldHaveReceived('error')->with('Could not write repeated HTTP error to activity log.', Mockery::type('array'))->once();
    }

    public function test_cache_failure_preserves_the_http_response(): void
    {
        config(['activitylog.repeated_http_errors.threshold' => 1]);
        Log::spy();
        Cache::shouldReceive('add')->andThrow(new RuntimeException('Cache unavailable'));

        $this->getJson('/test-http-error/404')->assertNotFound();

        $this->assertSame(0, Activity::inLog('exceptions')->count());
        Log::shouldHaveReceived('error')->with('Could not write repeated HTTP error to activity log.', Mockery::type('array'))->once();
    }
}
