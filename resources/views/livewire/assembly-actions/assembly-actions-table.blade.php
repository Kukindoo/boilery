<x-stellaris.livewire.table.wrapper heading="Pracovní úkony">
    <x-slot name="actions">
        @include('livewire.assembly-actions.partials.assembly-actions-table-actions')
    </x-slot>
    <x-slot name="filters">
        @include('livewire.assembly-actions.partials.assembly-actions-table-filters')
    </x-slot>
    <flux:table :paginate="$this->assemblyActions">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortBy === 'code'" :direction="$sortDirection" wire:click="sort('code')">
                Kód
            </flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">
                Název
            </flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'description'" :direction="$sortDirection" wire:click="sort('description')">
                Popis
            </flux:table.column>
            <flux:table.column>
                Akce
            </flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->assemblyActions as $assemblyAction)
                <flux:table.row :key="$assemblyAction->id">
                    <flux:table.cell>
                        {{ $assemblyAction->code }}
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $assemblyAction->name }}
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $assemblyAction->description ?? '—' }}
                    </flux:table.cell>
                    <flux:table.cell>
                        @include('livewire.assembly-actions.partials.assembly-actions-table-dropdown-actions')
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4">
                        Žádné pracovní úkony.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
    @include('livewire.modals.deletion', [
        'heading' => 'Smazat pracovní úkon?',
        'text' => 'Opravdu chcete smazat tento pracovní úkon? Úkon bude archivován.',
        'button' => 'Smazat',
        'cancel' => 'Zrušit',
    ])
</x-stellaris.livewire.table.wrapper>
