<?php

namespace App\Actions\Works;

use App\Dtos\Works\WorkDto;
use App\Enums\BoilerManufacturers;
use App\Models\Work;
use Illuminate\Support\Facades\DB;
use Throwable;

readonly class CreateWork
{
    public function __construct(private GenerateOrderNumber $generateOrderNumber) {}

    /**
     * @throws Throwable
     */
    public function handle(WorkDto $data): Work
    {
        return DB::transaction(function () use ($data): Work {
            $attributes = $data->toArray();

            if ($data->orderId === null || $data->orderId === '') {
                $manufacturer = BoilerManufacturers::tryFrom($data->typeOfProduct ?? '')
                    ?? BoilerManufacturers::UNKNOWN;
                $attributes['order_id'] = $this->generateOrderNumber->handle($manufacturer);
            }

            return Work::create($attributes);
        }, attempts: 5);
    }
}
