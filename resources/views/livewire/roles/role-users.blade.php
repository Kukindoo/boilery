<x-stellaris.livewire.table.wrapper heading="Uživatelé s touto rolí">
    <x-slot name="actions">
    </x-slot>
    <x-slot name="filters">
        @include('livewire.roles.partials.role-users-table-filters')
    </x-slot>
    <flux:table :paginate="$this->users">
        <flux:table.columns>
            <flux:table.column>
                Jméno a příjmení
            </flux:table.column>
            <flux:table.column>
                E-mail
            </flux:table.column>
            <flux:table.column>
                Akce
            </flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->users as $user)
                <flux:table.row :key="$user->id">
                    <flux:table.cell>
                        @can('view', $user)
                            <flux:link :href="route('customers.show', $user)">
                                {{ $user->name }}
                            </flux:link>
                        @else
                            {{ $user->name }}
                        @endcan
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $user->email }}
                    </flux:table.cell>
                    <flux:table.cell>
                        @include('livewire.roles.partials.role-users-table-dropdown-actions')
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="3">
                        Žádní uživatelé neodpovídají filtru.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
    <flux:error name="roleIds"/>
    @if ($removeUserId !== null)
        <livewire:modals.deletion
                wire:model="confirmingRemoval"
                :key="'remove-role-from-user-' . $role->id"
                @confirmed="removeRole"
                @dismissed="cancelRemoval"
                heading="Odebrat roli uživateli?"
                :text="__('Opravdu chcete uživateli odebrat roli :role?', ['role' => $role->name])"
                confirm-label="Odebrat roli"
        />
    @endif
</x-stellaris.livewire.table.wrapper>

