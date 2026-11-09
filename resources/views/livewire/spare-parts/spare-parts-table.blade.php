<x-stellaris.livewire.table.wrapper heading="Náhradní díly">
    <x-slot name="actions">
        @include('livewire.spare-parts.partials.spare-parts-table-actions')
    </x-slot>
    <x-slot name="filters">
        @include('livewire.spare-parts.partials.spare-parts-table-filters')
    </x-slot>
    <flux:table :paginate="$this->spareParts">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">Název</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'part_number'" :direction="$sortDirection" wire:click="sort('part_number')">Číslo dílu</flux:table.column>
            <flux:table.column>
                Cena
            </flux:table.column>
            <flux:table.column>Akce</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->spareParts as $sparePart)
                <flux:table.row :key="$sparePart->id">
                    <flux:table.cell>{{ $sparePart->name }}</flux:table.cell>
                    <flux:table.cell>{{ $sparePart->part_number ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        {{ $sparePart->formattedPrice() }}
                    </flux:table.cell>
                    <flux:table.cell>
                        @include('livewire.spare-parts.partials.spare-parts-table-dropdown-actions')
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row><flux:table.cell colspan="4">Žádné náhradní díly.</flux:table.cell></flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</x-stellaris.livewire.table.wrapper>
