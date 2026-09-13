<flux:card>
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading>Nedávné poptávky</flux:heading>
        </div>
        <flux:button size="sm" icon="plus" disabled>Přidat poptávku</flux:button>
    </div>
    <flux:table bleed :paginate="$this->quotes">
        <flux:table.columns>
            <flux:table.column>Požadavek</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'date'" :direction="$sortDirection"
                               wire:click="sort('date')">
                Datum
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
            @foreach ($this->quotes as $quote)
                <flux:table.row :key="$quote->id">
                    <flux:table.cell class="flex items-center gap-3">
                        {{ $quote->id }}
                    </flux:table.cell>

                    <flux:table.cell class="whitespace-nowrap">{{ $quote->created_at }}</flux:table.cell>

                    <flux:table.cell class="py-0">
                        @php
                            $status = \App\Enums\RequestedQuoteStatus::from($quote->status);
                        @endphp
                        <flux:badge size="sm" :color="$status->colour()">
                            {{ $status->label() }}
                        </flux:badge>
                    </flux:table.cell>

                    <flux:table.cell variant="strong">{{ $quote->boiler_type ?? 'Neznámý' }}</flux:table.cell>

                    <flux:table.cell class="py-0">
                        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal"></flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</flux:card>