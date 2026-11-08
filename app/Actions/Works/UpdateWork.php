<?php

namespace App\Actions\Works;

use App\Dtos\Works\WorkDto;
use App\Models\Work;
use App\Traits\LogsActionActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Throwable;

class UpdateWork
{
    use LogsActionActivity;

    /**
     * @throws Throwable
     */
    public function handle(Work $work, WorkDto $data): Work
    {
        try {
            return DB::transaction(function () use ($work, $data): Work {
                $work->refreshForUpdate();
                Gate::authorize('update', $work);

                $attributes = $data->toArray();
                Validator::make($attributes, [
                    'work_accepted_at' => ['required_with:work_started_at,work_completed_at', 'nullable', 'date_format:Y-m-d'],
                    'work_started_at' => ['required_with:work_completed_at', 'nullable', 'date_format:Y-m-d', 'after_or_equal:work_accepted_at'],
                    'work_completed_at' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:work_started_at'],
                ])->validate();

                $work->update($attributes);

                $this->logActionCompleted('works', $work, 'Work updated');

                return $work;
            }, attempts: 5);
        } catch (Throwable $exception) {
            $this->logActionAttempt('works', $work, $exception);

            throw $exception;
        }
    }
}
