@props(['work'])

@php
    $fields = [
        'Výrobce bojleru' => $work->boiler_manufacturer?->label() ?? '—',
        'Typ výrobku' => $work->type_of_product ?? '—',
        'Typové číslo' => $work->type_number ?? '—',
        'Výrobní číslo' => $work->serial_number ?? '—',
        'Datum nákupu' => $work->day_of_purchase?->format('d-m-Y') ?? '—',
        'Typ budovy' => $work->building_type ?? '—',
        'Oprava v záruce' => $work->repair_in_warranty ?? '—',
        'Záruka uznána' => $work->warranty_accepted ? 'Ano' : 'Ne',
    ];
@endphp

<x-stellaris.panel.base :header="__('Informace o bojleru')">
    <x-slot:action>
        @can('update', $work)
            <flux:button size="sm" :href="route('works.edit', $work)">
                Upravit
            </flux:button>
        @endcan
    </x-slot:action>
    <x-stellaris.panel.table.col-3-base>
        @foreach ($fields as $label => $value)
            <x-stellaris.container.show-row :title="$label">
                {{ $value }}
            </x-stellaris.container.show-row>
        @endforeach
    </x-stellaris.panel.table.col-3-base>
</x-stellaris.panel.base>
