@props(['work'])

@php
    $fields = [
        'Nahlášený problém' => $work->reported_problem ?? '—',
        'Provedené práce' => $work->work_performed_description ?? '—',
        'Poznámka' => $work->notes ?? '—',
    ];
@endphp

<x-stellaris.panel.base :header="__('Informace o opravě')">
    <x-stellaris.panel.table.col-3-base>
        @foreach ($fields as $label => $value)
            <x-stellaris.container.show-row :title="$label">
                {{ $value }}
            </x-stellaris.container.show-row>
        @endforeach
    </x-stellaris.panel.table.col-3-base>
</x-stellaris.panel.base>
