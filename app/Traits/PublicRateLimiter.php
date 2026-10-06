<?php

namespace App\Traits;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

trait PublicRateLimiter
{
    public function rateLimiter(string $limiterName): void
    {
        $limiter = RateLimiter::limiter($limiterName);
        $limits = $limiter(request(), $this->form->email);

        foreach ($limits as $limit) {
            $key = $limiterName . ':' . $limit->key;

            if (RateLimiter::tooManyAttempts($key, $limit->maxAttempts)) {
                throw ValidationException::withMessages([
                    'form.email' => __(
                        'Příliš mnoho pokusů. Zkuste to prosím znovu za :seconds sekund.',
                        ['seconds' => RateLimiter::availableIn($key)],
                    ),
                ]);
            }
        }

        foreach ($limits as $limit) {
            RateLimiter::increment(
                $limiterName . ':' . $limit->key,
                decaySeconds: $limit->decaySeconds,
            );
        }
    }
}
