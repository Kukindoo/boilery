<x-stellaris.livewire.table.wrapper heading="Moje poptávky">
    <x-slot:filters>
        <flux:input wire:model.live.debounce.300ms="search" label="Hledat v poptávkách" placeholder="Číslo poptávky, typ bojleru, štítek nebo zpráva" type="search" />
    </x-slot:filters>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Poptávka</flux:table.column>
            <flux:table.column>Stav</flux:table.column>
            <flux:table.column>Typ bojleru</flux:table.column>
            <flux:table.column>Vytvořeno</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($quotes as $quote)
                <flux:table.row :key="$quote->id">
                    <flux:table.cell>
                        <flux:link :href="route('quotes.show', $quote)">Poptávka #{{ $quote->id }}</flux:link>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$quote->status->colour()">{{ $quote->status->label() }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-normal">{{ $quote->boiler_type ?? 'Neznámý' }}</flux:table.cell>
                    <flux:table.cell>{{ $quote->created_at?->format('d. m. Y') }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4">
                        {{ trim($search) !== '' ? 'Žádné poptávky neodpovídají hledání.' : 'Zatím nemáte žádné poptávky.' }}
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{ $quotes->links() }}
</x-stellaris.livewire.table.wrapper>
