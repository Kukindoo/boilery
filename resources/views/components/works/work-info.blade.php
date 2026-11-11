@props(['work'])

@php
    $fields = [
        'Datum přijetí' => $work->work_accepted_at?->format('d-m-Y') ?? '—',
        'Datum zahájení' => $work->work_started_at?->format('d-m-Y') ?? '—',
        'Datum dokončení' => $work->work_completed_at?->format('d-m-Y') ?? '—',
        'Jméno technika' => $work->mechanic_name ?? '—',
    ];
@endphp

<x-stellaris.container.show-card :title="__('Informace o Zakázce')">
    <x-slot:action>
        @can('update', $work)
            <flux:button size="sm" :href="route('works.edit', $work)">
                Upravit
            </flux:button>
        @endcan
    </x-slot:action>

    <x-stellaris.container.show-row :title="__('Status')">
        <flux:badge size="sm" :color="$work->status->colour()">
            {{ $work->status->label() }}
        </flux:badge>
    </x-stellaris.container.show-row>

    <x-stellaris.container.show-row title="Zákazník">
        <x-customers.customer-name :customer="$work->user" />
    </x-stellaris.container.show-row>

    <x-stellaris.container.show-row title="Případ">
        @if ($work->issue && auth()->user()->can('view', $work->issue))
            <flux:link :href="route('issues.show', $work->issue)" wire:navigate>
                Případ #{{ $work->issue_id }}
            </flux:link>
        @else
            —
        @endif
    </x-stellaris.container.show-row>

    @foreach ($fields as $label => $value)
        <x-stellaris.container.show-row :title="$label">
            {{ $value }}
        </x-stellaris.container.show-row>
    @endforeach
</x-stellaris.container.show-card>
