<x-stellaris.livewire.table.wrapper heading="Zákazníci">
    <x-slot name="actions">
    </x-slot>
    <x-slot name="filters">
        @include('livewire.customers.partials.customers-table-filters')
    </x-slot>

    <flux:table :paginate="$this->customers">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">
                Jméno a příjmení
            </flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'email'" :direction="$sortDirection" wire:click="sort('email')">
                E-mail
            </flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'phone'" :direction="$sortDirection" wire:click="sort('phone')">
                Telefon
            </flux:table.column>
            <flux:table.column>
                Akce
            </flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->customers as $customer)
                <flux:table.row :key="$customer->id">
                    <flux:table.cell>
                        <flux:link :href="route('customers.show', $customer)">
                            {{ $customer->name }}
                        </flux:link>
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $customer->email }}
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $customer->phone ?: '—' }}
                    </flux:table.cell>
                    <flux:table.cell>
                        @include('livewire.customers.partials.customers-table-dropdown-actions')
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4">
                        Žádní zákazníci.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</x-stellaris.livewire.table.wrapper>
