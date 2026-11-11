@props(['quote'])

@php
    $fields = [
        'Telefon' => $quote->user->phone ?? '—',
        'E-mail' => $quote->user->email ?? '—',
        'Adresa' => $quote->user->address ?? 'Neznámá',
        'Nahlášeno' => $quote->created_at?->format('d.m.Y') ?? '—',
    ];
@endphp

<x-stellaris.container.show-card :title="__('Informace o zakazníkovi')">
    <x-slot:action>
        @can('update', $quote->user)
            <flux:button size="sm" icon="pencil" :href="route('customers.edit', $quote->user)">
                Upravit Zákazníka
            </flux:button>
        @endcan
    </x-slot:action>

    <x-stellaris.container.show-row title="Zákazník">
        <x-customers.customer-name :customer="$quote->user" :name="$quote->user->name"/>
    </x-stellaris.container.show-row>

    <x-stellaris.container.show-row title="Případ">
        @can ('view-any')
            <flux:link :href="route('issues.show', $quote->issue)" wire:navigate>
                Případ #{{ $quote->issue_id }}
            </flux:link>
        @else
            Případ #{{ $quote->issue_id }}
        @endcan
    </x-stellaris.container.show-row>

    @foreach ($fields as $label => $value)
        <x-stellaris.container.show-row :title="$label">
            {{ $value }}
        </x-stellaris.container.show-row>
    @endforeach
</x-stellaris.container.show-card>
