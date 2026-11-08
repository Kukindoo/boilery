<x-stellaris.livewire.table.wrapper heading="Náhradní díly">
    <x-slot name="actions">
        @include('livewire.spare-parts.partials.work-spare-parts-table-actions')
    </x-slot>
    <flux:table>
        <flux:table.columns>
            <flux:table.column>Název</flux:table.column>
            <flux:table.column>Číslo dílu</flux:table.column>
            <flux:table.column>
                Cena za kus
            </flux:table.column>
            <flux:table.column>
                Množství
            </flux:table.column>
            <flux:table.column>
                Celková cena
            </flux:table.column>
            <flux:table.column>
                Akce
            </flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($parts as $part)
                <flux:table.row :key="$part->id">
                    <flux:table.cell><flux:link :href="route('spare-parts.show', $part)">{{ $part->name }}</flux:link></flux:table.cell>
                    <flux:table.cell>{{ $part->part_number ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        {{ $part->formattedPrice() }}
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $part->pivot->amount }}
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $linePrices[$part->id]->format() }}
                    </flux:table.cell>
                    <flux:table.cell>
                        @include('livewire.spare-parts.partials.work-spare-parts-table-dropdown-actions')
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row><flux:table.cell colspan="6">K zakázce nejsou přidány žádné náhradní díly.</flux:table.cell></flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
    <div class="mt-4 flex justify-end">
        <flux:heading size="lg">
            Celkem za náhradní díly: {{ $totalPrice->format() }}
        </flux:heading>
    </div>
</x-stellaris.livewire.table.wrapper>
