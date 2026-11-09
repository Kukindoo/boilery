<x-layouts::app :title="$assemblyAction->name">
    <x-stellaris.container.show-card :title="$assemblyAction->name">
        <x-slot name="action">
            @include('assembly-actions.partials.show-actions')
        </x-slot>
        <x-stellaris.container.show-row title="Kód">
            {{ $assemblyAction->code }}
        </x-stellaris.container.show-row>

        <x-stellaris.container.show-row  title="Název">
            {{ $assemblyAction->name }}
        </x-stellaris.container.show-row>

        <x-stellaris.container.show-row title="Popis">
                {{ $assemblyAction->description ?? '—' }}
        </x-stellaris.container.show-row>

        <x-stellaris.container.show-row title="Vytvořeno">
            {{ $assemblyAction->created_at?->format('d-m-Y H:i') ?? '—' }}
        </x-stellaris.container.show-row>

        <x-stellaris.container.show-row title="Aktualizováno">
            {{ $assemblyAction->updated_at?->format('d-m-Y H:i') ?? '—' }}
        </x-stellaris.container.show-row>
    </x-stellaris.container.show-card>
</x-layouts::app>
