<?php

namespace App\Actions\Works;

use App\Dtos\Works\ServiceProtocolAssemblyActionDto;
use App\Dtos\Works\ServiceProtocolCustomerDto;
use App\Dtos\Works\ServiceProtocolDto;
use App\Dtos\Works\ServiceProtocolPartDto;
use App\Models\Work;
use App\ValueObjects\Price;
use Illuminate\Database\QueryException;
use InvalidArgumentException;
use OverflowException;

class PrepareServiceProtocolData
{
    /**
     * @throws QueryException
     * @throws InvalidArgumentException
     * @throws OverflowException
     */
    public function handle(Work $work): ServiceProtocolDto
    {
        $work->loadMissing('user');
        $parts = [];
        $partsTotal = Price::fromCents(0);
        foreach ($work->spareParts()->orderBy('spare_parts.id')->get() as $part) {
            $amount = (int) $part->pivot->getAttribute('amount');
            $lineTotal = Price::fromCents($part->price_in_cents)->multipliedBy($amount);
            $partsTotal = $partsTotal->plus($lineTotal);
            $parts[] = new ServiceProtocolPartDto(
                number: $part->part_number,
                name: $part->name,
                quantity: $amount,
                price: $lineTotal->format(),
            );
        }

        $assemblyActions = [];
        $assemblyActionsTotal = Price::fromCents(0);
        foreach ($work->assemblyActions()->orderBy('assembly_actions.id')->get() as $action) {
            $price = Price::fromCents((int) $action->pivot->getAttribute('price_in_cents'));
            $assemblyActionsTotal = $assemblyActionsTotal->plus($price);
            $assemblyActions[] = new ServiceProtocolAssemblyActionDto(
                code: $action->code,
                name: $action->name,
                price: $price->format(),
            );
        }

        return new ServiceProtocolDto(
            work: $work,
            customer: new ServiceProtocolCustomerDto(
                name: $work->user?->name,
                address: $work->user?->address,
                phone: $work->user?->phone,
            ),
            parts: $parts,
            partsTotal: $partsTotal->format(),
            assemblyActions: $assemblyActions,
            assemblyActionsTotal: $assemblyActionsTotal->format(),
        );
    }
}
