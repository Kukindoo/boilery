@props(['issue'])

<x-stellaris.panel.base :header="__('Informace o Případu')">
    <x-stellaris.panel.table.col-3-base>
        <x-stellaris.container.show-row title="Status">
            <flux:badge :color="$issue->status->colour()">
                {{ $issue->status->label() }}
            </flux:badge>
        </x-stellaris.container.show-row>
        <x-stellaris.container.show-row title="Zahájeno">
            {{ $issue->started_at?->format('d-m-Y H:i') ?? '—' }}
        </x-stellaris.container.show-row>
        <x-stellaris.container.show-row title="Dokončeno">
            {{ $issue->finished_at?->format('d-m-Y H:i') ?? '—' }}
        </x-stellaris.container.show-row>
        <x-stellaris.container.show-row title="Vytvořeno">
            {{ $issue->created_at?->format('d-m-Y H:i') ?? '—' }}
        </x-stellaris.container.show-row>
        <x-stellaris.container.show-row title="Aktualizováno">
            {{ $issue->updated_at?->format('d-m-Y H:i') ?? '—' }}
        </x-stellaris.container.show-row>
    </x-stellaris.panel.table.col-3-base>
</x-stellaris.panel.base>