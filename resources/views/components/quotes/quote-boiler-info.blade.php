@props(['quote'])

@php
    use App\Enums\BoilerManufacturers;

    $manufacturer = $quote->boiler_manufacturer;

        if (is_null($manufacturer)) {
            $manufacturer = BoilerManufacturers::UNKNOWN;
        }

    $fields = [
        'Zpráva' => $quote->message ?? '—',
        'Výrobce' => $manufacturer->label(),
        'Typ bojleru' => $quote->boiler_type ?? 'Neznámý',
        'Štítek' => $quote->boiler_serial_number ?? 'Neznámý',
        'Záruka' => $quote->under_warranty ? 'Ano' : 'Ne',
    ];
@endphp

<x-stellaris.container.show-card :title="__('Informace o poptávce')">
    <x-slot:action>
        @can('update', $quote)
            <flux:modal.trigger name="edit-personal-info">
                <flux:button icon="pencil" size="sm">
                    Upravit poptávku
                </flux:button>
            </flux:modal.trigger>
        @endcan
    </x-slot:action>

    @foreach ($fields as $label => $value)
        <x-stellaris.container.show-row :title="$label">
            {{ $value }}
        </x-stellaris.container.show-row>
    @endforeach
</x-stellaris.container.show-card>

<flux:modal name="edit-personal-info" class="w-full max-w-5xl">
    <div class="space-y-6">
        <flux:heading size="lg">
            Upravit informace o poptávce
        </flux:heading>

        <livewire:quotes.quote-edit :quote="$quote"/>
    </div>
</flux:modal>