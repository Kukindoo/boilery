<x-stellaris.container.show-card title="Role uživatele">
    <x-slot name="action">
        @can('manageRoles', $user)
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
                        Upravit role
                    </flux:button>
                @endif
            </div>
        @endcan
    </x-slot>

    <div class="space-y-4 col-span-3">
        <div class="flex flex-wrap items-start gap-2">
            @forelse ($roles as $role)
                @php
                    $selected = in_array($role->id, $roleIds, true);
                    $unassigned = $editing && ! $selected && ! in_array($role->id, $assignedIds, true);
                    $removed = $editing && ! $selected && ! $unassigned;
                @endphp
                @if ($unassigned)
                    <flux:badge as="button" rounded icon="plus" size="lg" color="amber"
                                wire:key="user-role-{{ $role->id }}" wire:click="toggleRole({{ $role->id }})"
                                :aria-label="'Přidat roli ' . $role->name" wire:loading.attr="disabled">
                        {{ $role->name }}
                    </flux:badge>
                @else
                    <flux:badge size="lg" class="justify-between gap-2" :color="$removed ? 'red' : 'green'" wire:key="user-role-{{ $role->id }}">
                        <span @class(['line-through' => $removed])>
                            {{ $role->name }}
                        </span>
                        @can('manageRoles', $user)
                            @if ($editing)
                                <flux:badge.close wire:click="toggleRole({{ $role->id }})"
                                                  :aria-label="($removed ? 'Obnovit roli ' : 'Odebrat roli ') . $role->name" wire:loading.attr="disabled" />
                            @endif
                        @endcan
                    </flux:badge>
                @endif
            @empty
                <flux:text>
                    Žádné role.
                </flux:text>
            @endforelse
        </div>
        <flux:error name="roleIds" />
        <flux:error name="roleIds.*" />
    </div>
</x-stellaris.container.show-card>
