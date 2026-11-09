<flux:dropdown position="bottom" align="end">
    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" :aria-label="__('Akce pro :name', ['name' => $assemblyAction->name])" />
    <flux:menu>
        <flux:menu.item icon="eye" :href="route('assembly-actions.show', $assemblyAction)">
            Detail
        </flux:menu.item>
        @can('update', $assemblyAction)
            <flux:menu.item icon="pencil" :href="route('assembly-actions.edit', $assemblyAction)">
                Upravit
            </flux:menu.item>
        @endcan
        @can('delete', $assemblyAction)
            <flux:menu.separator />
            <flux:menu.item variant="danger" wire:click="confirmDelete({{ $assemblyAction->id }})">
                Smazat
            </flux:menu.item>
        @endcan
    </flux:menu>
</flux:dropdown>
