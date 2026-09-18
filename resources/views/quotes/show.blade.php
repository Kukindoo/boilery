<x-layouts::app :title="__('Poptávky')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex items-center justify-between gap-4">
            <flux:heading>
                Poptávka {{ $quote->id }}
                <flux:text>{{ $quote->first_name }} {{ $quote->last_name }}</flux:text>
            </flux:heading>
            <flux:button size="sm" icon="printer" :href="route('quotes.print', $quote)" target="_blank">
                Vytisknout poptávku
            </flux:button>
        </div>

        <x-quotes.quote-person-info :quote="$quote" />
        <x-quotes.quote-boiler-info :quote="$quote" />
        <x-quotes.quote-attachments :quote="$quote" />

    </div>
</x-layouts::app>