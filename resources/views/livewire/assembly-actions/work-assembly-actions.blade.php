<x-stellaris.livewire.table.wrapper heading="Pracovní úkony">
    <x-slot name="actions">
        @includeWhen($actions->count() < 4, 'livewire.assembly-actions.partials.work-assembly-actions-table-actions')
    </x-slot>
    <flux:table>
        <flux:table.columns>
            <flux:table.column>
                Kód
            </flux:table.column>
            <flux:table.column>
                Název
            </flux:table.column>
            <flux:table.column>
                Cena
            </flux:table.column>
            <flux:table.column>
                Akce
            </flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($actions as $action)
                <livewire:assembly-actions.work-assembly-action-row
                    :work="$work"
                    :action="$action"
                    :price-in-cents="(int) $action->pivot->price_in_cents"
                    :key="'assembly-action-'.$work->id.'-'.$action->id.'-'.$action->pivot->price_in_cents"
                    @assembly-actions-changed="$refresh"
                />
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4">
                        K zakázce nejsou přidány žádné pracovní úkony.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
    <div class="mt-4 flex justify-end">
        <flux:heading size="lg">
            Celkem za pracovní úkony: {{ $totalPrice->format() }}
        </flux:heading>
    </div>
</x-stellaris.livewire.table.wrapper>
