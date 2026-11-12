@php
    use App\Models\User;
@endphp

<x-layouts::app :title="$role->name">
    <x-stellaris.panel.container.base>
        <x-slot:header>
            <x-stellaris.panel.header.base :heading="__('Role :role_id', ['role_id' => $role->id])">
            </x-stellaris.panel.header.base>
        </x-slot:header>
        <x-slot:actionsStrip>
            <x-stellaris.panel.actions-strip.base>
                <x-slot:slotLeft>
                    <flux:button :href="route('roles.index')" icon="arrow-left" size="sm">
                        {{ __('Zpět na Role') }}
                    </flux:button>
                </x-slot:slotLeft>
                @include('roles.partials.show-actions')
            </x-stellaris.panel.actions-strip.base>
        </x-slot:actionsStrip>

        <x-stellaris.panel.base :header="__('Informace o zakazníkovi')">
            <x-stellaris.panel.table.col-3-base>
                <x-stellaris.container.show-row title="Název">
                    {{ $role->name }}
                </x-stellaris.container.show-row>
            </x-stellaris.panel.table.col-3-base>
        </x-stellaris.panel.base>

        <livewire:roles.role-permissions :role="$role" />
        @can('viewAny', User::class)
            <livewire:roles.role-users :role="$role" />
        @endcan
    </x-stellaris.panel.container.base>
</x-layouts::app>
