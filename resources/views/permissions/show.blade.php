<x-layouts::app :title="$permission->name">
    <x-stellaris.panel.container.base>
        <x-slot:header>
            <x-stellaris.panel.header.base :heading="$permission->name">
                <x-slot:subheading>
                    <x-customers.customer-name :customer="$permission->user"/>
                </x-slot>
            </x-stellaris.panel.header.base>
        </x-slot:header>
        <x-slot:actionsStrip>
            <x-stellaris.panel.actions-strip.base>
                <x-slot:slotLeft>
                    <flux:button :href="route('permissions.index')" icon="arrow-left" size="sm">
                        {{ __('Zpět na Oprávnění') }}
                    </flux:button>
                </x-slot:slotLeft>
                @include('permissions.partials.show-actions')
            </x-stellaris.panel.actions-strip.base>
        </x-slot:actionsStrip>

        <x-stellaris.panel.base :header="__('Informace o oprávnění')">
            <x-stellaris.panel.table.col-3-base>
                <x-stellaris.container.show-row title="Název">
                    {{ $permission->name }}
                </x-stellaris.container.show-row>
            </x-stellaris.panel.table.col-3-base>
        </x-stellaris.panel.base>

        <livewire:permissions.permission-roles :permission="$permission"/>
    </x-stellaris.panel.container.base>
</x-layouts::app>
