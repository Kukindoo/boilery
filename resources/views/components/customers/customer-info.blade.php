@props(['customer'])

<x-stellaris.livewire.forms.side-to-side.section title="Informace o zákazníkovi">
    <dl class="divide-y divide-gray-100 dark:divide-white/5">
        @foreach (['Jméno a příjmení' => $customer->name, 'E-mail' => $customer->email, 'Telefon' => $customer->phone, 'Adresa' => $customer->address] as $label => $value)
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ $label }}
                </dt>
                <dd class="mt-1 whitespace-pre-wrap break-words text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-300">
                    {{ $value ?: '—' }}
                </dd>
            </div>
        @endforeach
    </dl>
</x-stellaris.livewire.forms.side-to-side.section>
