<?php

namespace App\Actions\Works;

use App\Dtos\Works\WorkDto;
use App\Enums\BoilerManufacturers;
use App\Models\Work;
use App\Traits\LogsActionActivity;
use Illuminate\Support\Facades\DB;
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
