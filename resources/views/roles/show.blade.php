@php
    use App\Models\User;
@endphp

<x-layouts::app :title="$role->name">
    <div class="space-y-6">
        <x-stellaris.container.show-card :title="$role->name">
            <x-slot name="action">
                <div class="flex gap-2">
                    <flux:button size="sm" :href="route('roles.index')">
                        Zpět
                    </flux:button>
                    @can('update', $role)
                        <flux:button size="sm" icon="pencil" :href="route('roles.edit', $role)">
                            Upravit
                        </flux:button>
                    @endcan
                </div>
            </x-slot>
            <x-stellaris.container.show-row title="Název">
                {{ $role->name }}
            </x-stellaris.container.show-row>
        </x-stellaris.container.show-card>
        <livewire:roles.role-permissions :role="$role" />
        @can('viewAny', User::class)
            <livewire:roles.role-users :role="$role" />
        @endcan
    </div>
</x-layouts::app>
