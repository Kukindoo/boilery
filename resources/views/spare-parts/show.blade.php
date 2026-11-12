@php
    $fields = [
        'Název' => $sparePart->name ,
        'Číslo dílu' => $sparePart->part_number ?? '—',
        'Popis' => $sparePart->description ?? '—',
        'Cena' => $sparePart->formattedPrice(),
        'Vytvořeno' => $sparePart->created_at?->format('d-m-Y H:i') ?? '—',
        'Aktualizováno' => $sparePart->updated_at?->format('d-m-Y H:i') ?? '—',
    ];
@endphp

<x-layouts::app :title="$sparePart->name">
    <x-stellaris.panel.container.base>
        <x-slot:header>
            <x-stellaris.panel.header.base
                    :heading="__('Náhradní díl :spare_part_id', ['spare_part_id' => $sparePart->id])">
            </x-stellaris.panel.header.base>
        </x-slot:header>
        <x-slot:actionsStrip>
            <x-stellaris.panel.actions-strip.base>
                <x-slot:slotLeft>
                    <flux:button :href="route('spare-parts.index')" icon="arrow-left" size="sm">
                        {{ __('Zpět na Náhradní Díly') }}
                    </flux:button>
                </x-slot:slotLeft>
                @include('spare-parts.partials.show-actions')
            </x-stellaris.panel.actions-strip.base>
        </x-slot:actionsStrip>

        <x-stellaris.panel.base :header="__('Informace o Náhradním Dílu')">
            <x-stellaris.panel.table.col-3-base>
                @foreach ($fields as $key => $value)
                    <x-stellaris.container.show-row :title="$key">
                        {{ $value }}
                    </x-stellaris.container.show-row>
                @endforeach
            </x-stellaris.panel.table.col-3-base>
        </x-stellaris.panel.base>
    </x-stellaris.panel.container.base>
</x-layouts::app>
