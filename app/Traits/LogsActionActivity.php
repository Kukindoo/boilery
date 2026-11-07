<?php

namespace App\Traits;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

trait LogsActionActivity
{
    protected function logActionCompleted(string $logName, Model $subject, string $description, array $properties = []): void
    {
        activity($logName)
            ->performedOn($subject)
            ->event('action_completed')
            ->withProperties(['action' => static::class, ...$properties])
            ->log($description);
    }

    protected function logActionAttempt(string $logName, ?Model $subject, Throwable $exception, array $properties = []): void
    {
        $causer = auth()->user();
        $attemptId = (string) Str::uuid();
        $action = static::class;
        $subject = $subject?->withoutRelations();
        $write = function () use ($logName, $subject, $exception, $properties, $causer, $attemptId, $action): void {
            $activity = activity($logName)
                ->event('action_attempted')
                ->withProperties([
                    ...$properties,
                    'action' => $action,
                    'attempt_id' => $attemptId,
                    'exception' => $exception::class,
                ]);

            if ($subject?->getKey() !== null) {
                $activity->performedOn($subject);
            }

            if ($causer !== null) {
                $activity->causedBy($causer);
            } else {
                $activity->causedByAnonymous();
            }

            $activity->log(class_basename($action) . ' attempted');
        };

        $this->persistActionAttempt($write);
    }

    private function persistActionAttempt(Closure $write): void
    {
        try {
            // A failed nested action may still be inside its caller's transaction.
            // Recreate this trace if that enclosing transaction later rolls back.
            if (DB::transactionLevel() > 0) {
                DB::afterRollBack(fn () => $this->persistActionAttempt($write));
            }

            $write();
        } catch (Throwable $loggingException) {
            // Logging failure must not replace the action's original exception.
            report($loggingException);
        }
    }
}
