<x-layouts::app :title="__('Poptávky')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex items-center justify-between gap-4">
            <flux:heading>
                Poptávka {{ $quote->id }}
                <flux:text>{{ $quote->first_name }} {{ $quote->last_name }}</flux:text>
            </flux:heading>
            <div class="flex flex-wrap items-start justify-end gap-2">
                <livewire:quotes.quote-pdf-generation :quote="$quote" />
                @if( auth()->user()->can('accept', $quote) )
                    <form method="POST" action="{{ route('quotes.accept', $quote) }}" class="inline-block">
                        @csrf
                        <flux:button type="submit" icon="check" size="sm">
                            Přijmout poptávku
                        </flux:button>
                    </form>
                @endif
            </div>
        </div>
        <x-quotes.quote-person-info :quote="$quote" />
        <x-quotes.quote-boiler-info :quote="$quote" />
        <x-quotes.quote-attachments :quote="$quote" />

    </div>
</x-layouts::app>
