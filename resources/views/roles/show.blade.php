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
        <x-stellaris.container.show-card :title="__('Oprávnění')">
            @forelse ($permissionGroups as $modelType => $permissions)
                @if(! $loop->first)
                    <flux:separator class="col-span-3" />
                @endif
                <x-stellaris.container.show-row :title="ucfirst($modelType)">
                    <div class="space-y-2">
                        <div class="grid grid-cols-1 items-start gap-4 md:grid-cols-2">
                            @foreach (['all' => 'ALL', 'own' => 'OWN', 'other' => 'Další oprávnění'] as $scope => $label)
                                @if ($scope !== 'other' || isset($permissionScopes[$modelType][$scope]))
                                        <div class="grid grid-cols-1 gap-2">
                                            @forelse ($permissionScopes[$modelType][$scope] ?? [] as $permission)
                                                <flux:badge :href="route('permissions.show', $permission)">
                                                    {{ $permission->name }}
                                                </flux:badge>
                                            @empty
                                                <flux:text>
                                                    —
                                                </flux:text>
                                            @endforelse
                                        </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </x-stellaris.container.show-row>
            @empty
                —
            @endforelse
        </x-stellaris.container.show-card>
    </div>
</x-layouts::app>
