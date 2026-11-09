<x-layouts::app :title="$permission->name">
    <div class="space-y-6">
        <x-stellaris.container.show-card :title="$permission->name">
            <x-slot name="action">
                <flux:button size="sm" :href="route('permissions.index')">
                    Zpět
                </flux:button>
            </x-slot>
            <x-stellaris.container.show-row title="Název">
                {{ $permission->name }}
            </x-stellaris.container.show-row>
        </x-stellaris.container.show-card>
        <livewire:permissions.permission-roles :permission="$permission" />
    </div>
</x-layouts::app>
