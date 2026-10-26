<x-stellaris.livewire.table.wrapper heading="Nedávné poptávky">
    <x-slot name="actions">
        @include('livewire.quotes.partials.quotes-table-actions')
    </x-slot>
    <x-slot name="filters">
        @include('livewire.quotes.partials.quotes-table-filters')
    </x-slot>
    <flux:table bleed :paginate="$this->quotes">
        <flux:table.columns>
            <flux:table.column>
                Požadavek
            </flux:table.column>
            <flux:table.column>
                Jméno a Příjmení
            </flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'status'" :direction="$sortDirection"
                               wire:click="sort('status')">
                Status
            </flux:table.column>
            <flux:table.column>
                Typ bojleru
            </flux:table.column>
            <flux:table.column>
            </flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->quotes as $quote)
                <flux:table.row :key="$quote->id">
                    <flux:table.cell class="flex items-center gap-3">
                        {{ $quote->id }}
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">
                        <flux:link wire:click="openQuote({{ $quote }})" style="cursor:pointer">
                            {{ $quote->first_name }} {{ $quote->last_name }}
                        </flux:link>
                    </flux:table.cell>
                    <flux:table.cell class="py-0">
                        @php
                            $status = $quote->status;
                        @endphp
                        <flux:badge size="sm" :color="$status->colour()">
                            {{ $status->label() }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell variant="strong">
                        {{ $quote->boiler_type ?? 'Neznámý' }}
                    </flux:table.cell>
                    <flux:table.cell class="py-0">
                        @include('livewire.quotes.partials.quotes-table-dropdown-actions')
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5">
                        Žádné poptávky.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
    @include('livewire.modals.deletion', [
        'heading' => 'Smazat Poptávku?',
        'text' => 'Opravdu chcete smazat tuto poptávku? Poptávka bude archivovaná.',
        'button' => 'Smazat',
        'cancel' => 'Zrušit',
    ])
</x-stellaris.livewire.table.wrapper>