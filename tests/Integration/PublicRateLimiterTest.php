<?php

namespace Integration;

use App\Traits\PublicRateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Tests\Integration\IntegrationTestCase;

class PublicRateLimiterTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::for('test-submission', function (): array {
            return [
                Limit::perMinute(10)->by('ip'),
                Limit::perMinute(3)->by('email'),
            ];
        });
    }

    public function test_allowed_submission_counts_both_limits(): void
    {
        $subject = $this->subject();

        $subject->rateLimiter('test-submission');

        $this->assertSame(1, RateLimiter::attempts('test-submission:ip'));
        $this->assertSame(1, RateLimiter::attempts('test-submission:email'));
    }

    public function test_blocked_submission_does_not_increment_either_limit(): void
    {
        $this->freezeTime();
        $subject = $this->subject();

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $subject->rateLimiter('test-submission');
        }

        try {
            $subject->rateLimiter('test-submission');

            $this->fail('Expected the fourth submission to be blocked.');
        } catch (ValidationException $exception) {
            $this->assertSame([
                'form.email' => [
                    __('Please try again in :seconds seconds.', [
                        'seconds' => 60,
                    ]),
                ],
            ], $exception->errors());
        }

        $this->assertSame(3, RateLimiter::attempts('test-submission:ip'));
        $this->assertSame(3, RateLimiter::attempts('test-submission:email'));
    }

    public function test_submission_is_allowed_after_limits_expire(): void
    {
        $this->freezeTime();
        $subject = $this->subject();

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $subject->rateLimiter('test-submission');
        }

        $this->travel(61)->seconds();

        $subject->rateLimiter('test-submission');

        $this->assertSame(1, RateLimiter::attempts('test-submission:ip'));
        $this->assertSame(1, RateLimiter::attempts('test-submission:email'));
    }

    private function subject(): object
    {
        return new class
        {
            use PublicRateLimiter;

            public object $form;

            public function __construct()
            {
                $this->form = (object) ['email' => 'customer@example.com'];
            }
        };
    }
}
