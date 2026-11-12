@props(['issue'])

@php
    $fields = [
        'Zahájeno' => $issue->started_at?->format('d-m-Y H:i') ?? '—',
        'Dokončeno' => $issue->finished_at?->format('d-m-Y H:i') ?? '—',
        'Vytvořeno' => $issue->created_at?->format('d-m-Y H:i') ?? '—',
        'Aktualizováno' => $issue->updated_at?->format('d-m-Y H:i') ?? '—',
    ];
@endphp

<x-stellaris.panel.base :header="__('Informace o Případu')">
    <x-stellaris.panel.table.col-3-base>
        <x-stellaris.container.show-row title="Status">
            <flux:badge :color="$issue->status->colour()">
                {{ $issue->status->label() }}
            </flux:badge>
        </x-stellaris.container.show-row>
        @foreach ($fields as $label => $value)
            <x-stellaris.container.show-row :title="$label">
                {{ $value }}
            </x-stellaris.container.show-row>
        @endforeach
    </x-stellaris.panel.table.col-3-base>
</x-stellaris.panel.base>