<x-stellaris.livewire.table.wrapper heading="Role">
    <x-slot name="filters">
        @include('livewire.roles.partials.roles-table-filters')
    </x-slot>
    <flux:table :paginate="$this->roles">
        <flux:table.columns>
            <flux:table.column sortable :sorted="true" :direction="$sortDirection" wire:click="sort">
                Název
            </flux:table.column>
            <flux:table.column>
                Oprávnění
            </flux:table.column>
            <flux:table.column>
                Uživatelé
            </flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->roles as $role)
                <flux:table.row :key="$role->id">
                    <flux:table.cell>
                        <flux:link :href="route('roles.show', $role)">
                            {{ $role->name }}
                        </flux:link>
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $role->permissions_count }}
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $role->users_count }}
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="3">
                        Žádné záznamy.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</x-stellaris.livewire.table.wrapper>
