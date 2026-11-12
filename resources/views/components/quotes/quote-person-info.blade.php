@props(['quote'])

@php
    $fields = [
        'Telefon' => $quote->user->phone ?? '—',
        'E-mail' => $quote->user->email ?? '—',
        'Adresa' => $quote->user->address ?? 'Neznámá',
        'Nahlášeno' => $quote->created_at?->format('d.m.Y') ?? '—',
    ];
@endphp

<x-stellaris.panel.base :header="__('Informace o zakazníkovi')">
    <x-slot:action>
        @can('update', $quote->user)
            <flux:button size="sm" icon="pencil" :href="route('customers.edit', $quote->user)">
                Upravit Zákazníka
            </flux:button>
        @endcan
    </x-slot:action>

    <x-stellaris.panel.table.col-3-base>
        <x-stellaris.container.show-row title="Zákazník">
            <x-customers.customer-name :customer="$quote->user" :name="$quote->user->name"/>
        </x-stellaris.container.show-row>

        <x-stellaris.container.show-row title="Případ">
            <x-issues.issue-name :quote="$quote" />
        </x-stellaris.container.show-row>

        @foreach ($fields as $label => $value)
            <x-stellaris.container.show-row :title="$label">
                {{ $value }}
            </x-stellaris.container.show-row>
        @endforeach
    </x-stellaris.panel.table.col-3-base>

</x-stellaris.panel.base>
