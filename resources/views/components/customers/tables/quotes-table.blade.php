@props(['quotes'])

<x-stellaris.container.index-card heading="Poptávky zákazníka">
    <flux:table class="w-full min-w-160 table-fixed">
        <flux:table.columns>
            <flux:table.column class="w-1/4">
                Požadavek
            </flux:table.column>
            <flux:table.column class="w-1/4">
                Status
            </flux:table.column>
            <flux:table.column class="w-1/4">
                Typ bojleru
            </flux:table.column>
            <flux:table.column class="w-1/4">
                Vytvořeno
            </flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($quotes as $quote)
                <flux:table.row>
                    <flux:table.cell class="whitespace-normal wrap-anywhere">
                        @can('view', $quote)
                            <flux:link :href="route('quotes.show', $quote)">
                                #{{ $quote->id }}
                            </flux:link>
                        @else
                            #{{ $quote->id }}
                        @endcan
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$quote->status->colour()">
                            {{ $quote->status->label() }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-normal wrap-anywhere">
                        {{ $quote->boiler_type ?? 'Neznámý' }}
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $quote->created_at?->format('d. m. Y') }}
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4">
                        Žádné poptávky.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
    {{ $quotes->links() }}
</x-stellaris.container.index-card>
