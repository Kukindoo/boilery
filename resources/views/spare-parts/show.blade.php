<x-layouts::app :title="$sparePart->name">
    <x-stellaris.container.show-card :title="$sparePart->name">
        <x-slot name="actions">
            <flux:button :href="route('spare-parts.index')">Zpět na seznam</flux:button>
            @can('update', $sparePart)
                <flux:button :href="route('spare-parts.edit', $sparePart)">Upravit</flux:button>
            @endcan
        </x-slot>
        <x-stellaris.container.show-row  title="Název">
            {{ $sparePart->name }}
        </x-stellaris.container.show-row>
        <x-stellaris.container.show-row title="Číslo dílu">
            {{ $sparePart->part_number ?? '—' }}
        </x-stellaris.container.show-row>
        <x-stellaris.container.show-row title="Popis">
            <div class="whitespace-pre-wrap">{{ $sparePart->description ?? '—' }}</div>
        </x-stellaris.container.show-row>
        <x-stellaris.container.show-row title="Cena">
            {{ $sparePart->formattedPrice() }}
        </x-stellaris.container.show-row>
        <x-stellaris.container.show-row title="Vytvořeno">
            {{ $sparePart->created_at?->format('d-m-Y H:i') ?? '—' }}
        </x-stellaris.container.show-row>
        <x-stellaris.container.show-row title="Aktualizováno">
            {{ $sparePart->updated_at?->format('d-m-Y H:i') ?? '—' }}
        </x-stellaris.container.show-row>
    </x-stellaris.container.show-card>
</x-layouts::app>
