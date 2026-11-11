@props(['works', 'heading' => 'Zakázky zákazníka', 'paginate' => true])

<x-stellaris.livewire.table.wrapper :heading="$heading">
    <flux:table class="w-full min-w-160 table-fixed">
        <flux:table.columns>
            <flux:table.column class="w-1/4">
                Číslo zakázky
            </flux:table.column>
            <flux:table.column class="w-1/4">
                Status
            </flux:table.column>
            <flux:table.column class="w-1/4">
                Typ výrobku
            </flux:table.column>
            <flux:table.column class="w-1/4">
                Vytvořeno
            </flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($works as $work)
                <flux:table.row>
                    <flux:table.cell class="whitespace-normal wrap-anywhere">
                        @can('view', $work)
                            <flux:link :href="route('works.show', $work)">
                                {{ $work->order_id }}
                            </flux:link>
                        @else
                            {{ $work->order_id }}
                        @endcan
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$work->status->colour()">
                            {{ $work->status->label() }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-normal wrap-anywhere">
                        {{ $work->type_of_product ?? 'Neznámý' }}
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $work->created_at?->format('d. m. Y') }}
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4">
                        Žádné zakázky.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
    @if ($paginate)
        {{ $works->links() }}
    @endif
</x-stellaris.livewire.table.wrapper>
