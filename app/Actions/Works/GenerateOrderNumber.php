<?php

namespace App\Actions\Works;

use App\Enums\BoilerManufacturers;
use App\Models\Work;

class GenerateOrderNumber
{
    public function handle(BoilerManufacturers $manufacturer): string
    {
        $date = now()->format('Ymd');
        $lastSequence = Work::withTrashed()
            ->where('order_id', 'like', '%' . $date . '_')
            ->lockForUpdate()
            ->pluck('order_id')
            ->map(function (string $orderNumber) use ($date): int {
                if (preg_match('/^([1-9][0-9]*)' . $date . '[A-Z]$/', $orderNumber, $matches) !== 1) {
                    return 0;
                }

                return (int) $matches[1];
            })
            ->max() ?? 0;

        return ($lastSequence + 1) . $date . $manufacturer->orderNumberPrefix();
    }
}
