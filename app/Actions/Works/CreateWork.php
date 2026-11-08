<?php

namespace App\Actions\Works;

use App\Dtos\Works\WorkDto;
use App\Enums\BoilerManufacturers;
use App\Models\Work;
use App\Traits\LogsActionActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

readonly class CreateWork
{
    use LogsActionActivity;

    public function __construct(private GenerateOrderNumber $generateOrderNumber) {}

    /**
     * @throws Throwable
     */
    public function handle(WorkDto $data): Work
    {
        try {
            return DB::transaction(function () use ($data): Work {
                $attributes = $data->toArray();
                Validator::make($attributes, [
                    'work_accepted_at' => ['required_with:work_started_at,work_completed_at', 'nullable', 'date_format:Y-m-d'],
                    'work_started_at' => ['required_with:work_completed_at', 'nullable', 'date_format:Y-m-d', 'after_or_equal:work_accepted_at'],
                    'work_completed_at' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:work_started_at'],
                ])->validate();

                if ($data->orderId === null || $data->orderId === '') {
                    $manufacturer = $data->boilerManufacturer ?? BoilerManufacturers::UNKNOWN;
                    $attributes['order_id'] = $this->generateOrderNumber->handle($manufacturer);
                }

                $work = Work::create($attributes);
                $this->logActionCompleted('works', $work, 'Work created');

                return $work;
            }, attempts: 5);
        } catch (Throwable $exception) {
            $this->logActionAttempt('works', null, $exception, ['user_id' => $data->userId, 'quote_id' => $data->quoteId]);

            throw $exception;
        }
    }
}
