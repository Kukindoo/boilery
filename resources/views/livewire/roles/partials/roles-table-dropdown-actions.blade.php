<flux:dropdown position="bottom" align="end">
    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" :aria-label="__('Akce pro :name', ['name' => $role->name])" />
    <flux:menu>
        <flux:menu.item icon="eye" :href="route('roles.show', $role)">
            Detail
        </flux:menu.item>
        @can('update', $role)
            <flux:menu.item icon="pencil" :href="route('roles.edit', $role)">
                Upravit
            </flux:menu.item>
        @endcan
        @can('delete', $role)
            <flux:menu.separator />
            <flux:menu.item variant="danger" wire:click="confirmDelete({{ $role->id }})">
                Smazat
            </flux:menu.item>
        @endcan
    </flux:menu>
</flux:dropdown>
