<x-layouts::app :title="__('Poptávky')">
    <x-stellaris.container.index-card :heading="__('Poptávka :quote_id', ['quote_id' => $quote->id])">
        <x-slot:subheading>
            <x-customers.customer-name :customer="$quote->user" />
        </x-slot>
        <x-slot:actions>
                <livewire:quotes.quote-pdf-generation :quote="$quote" />
                @if( auth()->user()->can('accept', $quote) )
                    <form method="POST" action="{{ route('quotes.accept', $quote) }}" class="inline-block">
                        @csrf
                        <flux:button type="submit" icon="check" size="sm">
                            Přijmout poptávku
                        </flux:button>
                    </form>
                @endif
        </x-slot:actions>

        <x-quotes.quote-person-info :quote="$quote" />
        <x-quotes.quote-boiler-info :quote="$quote" />
        <x-quotes.quote-attachments :quote="$quote" />
    </x-stellaris.container.index-card>
</x-layouts::app>
