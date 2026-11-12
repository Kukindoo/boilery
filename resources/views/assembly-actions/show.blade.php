@php
    $fields = [
        "Kód" => $assemblyAction->code,
        "Název" => $assemblyAction->name,
        "Popis" => $assemblyAction->description ?? '—',
        "Vytvořeno" => $assemblyAction->created_at?->format('d-m-Y H:i') ?? '—',
        "Aktualizováno" => $assemblyAction->updated_at?->format('d-m-Y H:i') ?? '—',
];
@endphp

<x-layouts::app :title="$assemblyAction->name">
    <x-stellaris.panel.container.base>
        <x-slot:header>
            <x-stellaris.panel.header.base
                    :heading="__('Úkon :assembly_action_id', ['assembly_action_id' => $assemblyAction->id])"/>
        </x-slot:header>
        <x-slot:actionsStrip>
            <x-stellaris.panel.actions-strip.base>
                <x-slot:slotLeft>
                    <flux:button :href="route('assembly-actions.index')" icon="arrow-left" size="sm">
                        {{ __('Zpět na Úkony') }}
                    </flux:button>
                </x-slot:slotLeft>
                @include('assembly-actions.partials.show-actions')
            </x-stellaris.panel.actions-strip.base>
        </x-slot:actionsStrip>

        <x-stellaris.panel.base :header="__('Informace o úkonu')">
            <x-stellaris.panel.table.col-3-base>
                @foreach( $fields as $title => $value)
                    <x-stellaris.container.show-row :title="$title">
                        {{ $value }}
                    </x-stellaris.container.show-row>

                @endforeach
            </x-stellaris.panel.table.col-3-base>
        </x-stellaris.panel.base>
    </x-stellaris.panel.container.base>
</x-layouts::app>
