<x-stellaris.livewire.table.wrapper heading="Uživatelé">
    <x-slot name="actions">
    </x-slot>
    <x-slot name="filters">
        @include('livewire.users.partials.users-table-filters')
    </x-slot>

    <flux:table :paginate="$this->users">
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
                Role
            </flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->users as $user)
                <flux:table.row :key="$user->id">
                    <flux:table.cell>
                        <flux:link :href="route('customers.show', $user)">
                            {{ $user->name }}
                        </flux:link>
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $user->email }}
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $user->phone ?: '—' }}
                    </flux:table.cell>
                    <flux:table.cell>
                        @forelse ($user->roles as $role)
                            <flux:badge size="sm">{{ $role->name }}</flux:badge>
                        @empty
                            —
                        @endforelse
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4">
                        Žádní uživatelé.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</x-stellaris.livewire.table.wrapper>
