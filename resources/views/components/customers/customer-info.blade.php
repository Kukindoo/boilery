@props(['customer'])
@php
    $fields = [
        'Jméno a příjmení' => $customer->name,
        'E-mail' => $customer->email,
        'Telefon' => $customer->phone,
        'Adresa' => $customer->address,
    ];
@endphp

<x-stellaris.container.show-card :title="__('Informace o Zakázce')">
        @foreach ($fields as $label => $value)
            <x-stellaris.container.show-row :title="$label">
                {{ $value ?: '—' }}
            </x-stellaris.container.show-row>
        @endforeach
</x-stellaris.container.show-card>
