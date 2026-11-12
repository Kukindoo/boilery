@props(['issue'])

@php
    $fields = [
        'Telefon' => $issue->user->phone ?? '—',
        'E-mail' => $issue->user->email ?? '—',
        'Adresa' => $issue->user->address ?? 'Neznámá',
    ];
@endphp

<x-stellaris.panel.base :header="__('Informace o zakazníkovi')">

    <x-stellaris.panel.table.col-3-base>
        <x-stellaris.container.show-row title="Zákazník">
            <x-customers.customer-name :customer="$issue->user" :name="$issue->user->name"/>
        </x-stellaris.container.show-row>
        @foreach ($fields as $label => $value)
            <x-stellaris.container.show-row :title="$label">
                {{ $value }}
            </x-stellaris.container.show-row>
        @endforeach
    </x-stellaris.panel.table.col-3-base>

</x-stellaris.panel.base>