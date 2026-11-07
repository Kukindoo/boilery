@props(['quote'])

@php
    $fields = [
        'Telefon' => $quote->user->phone ?? '—',
        'E-mail' => $quote->user->email ?? '—',
        'Adresa' => $quote->user->address ?? 'Neznámá',
        'Zpráva' => $quote->message ?? '—',
        'Nahlášeno' => $quote->created_at?->format('d.m.Y') ?? '—',
    ];
@endphp

<x-stellaris.container.show-card :title="__('Informace o poptávce')">
    <x-slot:action>
        @can('update', $quote)
            <flux:modal.trigger name="edit-personal-info">
                <flux:button size="sm">
                    Upravit
                </flux:button>
            </flux:modal.trigger>
        @endcan
    </x-slot:action>

    <x-stellaris.container.show-row title="Zákazník">
        <x-customers.customer-name :customer="$quote->user" />
    </x-stellaris.container.show-row>

    @foreach ($fields as $label => $value)
        <x-stellaris.container.show-row :title="$label">
            {{ $value }}
        </x-stellaris.container.show-row>
    @endforeach
</x-stellaris.container.show-card>

<flux:modal name="edit-personal-info" class="w-full max-w-5xl">
    <div class="space-y-6">
        <flux:heading size="lg">
            Upravit informace o poptávce
        </flux:heading>

        <livewire:quotes.quote-edit :quote="$quote"/>
    </div>
</flux:modal>
