@props(['quote'])

@php
    use App\Enums\BoilerManufacturers;

    $manufacturer = $quote->boiler_manufacturer;

        if (is_null($manufacturer)) {
            $manufacturer = BoilerManufacturers::UNKNOWN;
        }

    $fields = [
        'Výrobce' => $manufacturer->label(),
        'Typ bojleru' => $quote->boiler_type ?? 'Neznámý',
        'Štítek' => $quote->boiler_serial_number ?? 'Neznámý',
        'Adresa' => $quote->user?->address ?? 'Neznámá',
    ];
@endphp

<x-stellaris.container.show-card :title="__('Informace o Bojleru')">
    @foreach ($fields as $label => $value)
        <x-stellaris.container.show-row :title="$label">
            {{ $value }}
        </x-stellaris.container.show-row>
    @endforeach
</x-stellaris.container.show-card>