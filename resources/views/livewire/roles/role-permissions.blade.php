@php
    use App\Enums\Permissions;
@endphp

<x-stellaris.container.show-card :title="__('Oprávnění')">
    <x-slot name="action">
        @can('update', $role)
            <div class="flex gap-2">
                @if ($editing)
                    <flux:button size="sm" variant="primary" wire:click="save" wire:loading.attr="disabled">
                        Uložit
                    </flux:button>
                    <flux:button size="sm" wire:click="cancel">
                        Zrušit
                    </flux:button>
                @else
                    <flux:button size="sm" icon="pencil" wire:click="edit">
                        Upravit oprávnění
                    </flux:button>
                @endif
            </div>
        @endcan
    </x-slot>

    @forelse ($permissionGroups as $modelType => $permissions)
        @if (! $loop->first)
            <flux:separator class="col-span-3" />
        @endif
        <x-stellaris.container.show-row :title="$modelType === 'other' ? 'Ostatní' : ucfirst($modelType)">
            <div class="space-y-4" wire:key="role-permissions-{{ $modelType }}">
                <div class="grid grid-cols-1 items-start gap-4 md:grid-cols-2">
                    @foreach (['all', 'own', 'other'] as $scope)
                        @if ($scope !== 'other' || isset($permissionScopes[$modelType][$scope]))
                            <div @class(['grid grid-cols-1 gap-2', 'md:col-span-2' => $scope === 'other'])>
                                @forelse ($permissionScopes[$modelType][$scope] ?? [] as $permission)
                                    @php
                                        $selected = in_array($permission->id, $permissionIds, true);
                                        $unassigned = $editing && ! $selected && ! in_array($permission->id, $assignedIds, true);
                                        $removed = $editing && ! $selected && ! $unassigned;
                                    @endphp
                                    @if ($unassigned)
                                        <flux:badge as="button" rounded icon="plus" size="lg" color="amber" :title="$permission->name"
                                                    wire:key="role-permission-{{ $permission->id }}" wire:click="togglePermission({{ $permission->id }})"
                                                    :aria-label="'Přidat oprávnění ' . $permission->name" wire:loading.attr="disabled">
                                            {{ Permissions::fromLabel($permission->name)?->badgeLabel() ?? $permission->name }}
                                        </flux:badge>
                                    @else
                                        <flux:badge size="lg" class="justify-between" :title="$permission->name"
                                                    :href="$editing ? null : route('permissions.show', $permission)" wire:key="role-permission-{{ $permission->id }}"
                                                    :color="$removed ? 'red' : 'green'">
                                            <span @class(['line-through' => $removed])>
                                                {{ Permissions::fromLabel($permission->name)?->badgeLabel() ?? $permission->name }}
                                            </span>
                                            @can('update', $role)
                                                @if ($editing)
                                                    <flux:badge.close wire:click="togglePermission({{ $permission->id }})"
                                                                      :aria-label="($removed ? 'Obnovit oprávnění ' : 'Odebrat oprávnění ') . $permission->name" wire:loading.attr="disabled" />
                                                @endif
                                            @endcan
                                        </flux:badge>
                                    @endif
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
        <flux:text>
            Žádná oprávnění.
        </flux:text>
    @endforelse
    <div class="col-span-3">
        <flux:error name="permissionIds" />
        <flux:error name="permissionIds.*" />
        <flux:error name="form.permissionIds" />
    </div>
</x-stellaris.container.show-card>
