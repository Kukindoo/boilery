<flux:dropdown position="bottom" align="end">
    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" :aria-label="__('Akce pro :name', ['name' => $sparePart->name])" />
    <flux:menu>
        <flux:menu.item icon="eye" :href="route('spare-parts.show', $sparePart)">
            Detail
        </flux:menu.item>
        @can('update', $sparePart)
            <flux:menu.item icon="pencil" :href="route('spare-parts.edit', $sparePart)">
                Upravit
            </flux:menu.item>
        @endcan
        @can('delete', $sparePart)
            <flux:menu.separator />
            <flux:menu.item variant="danger" wire:click="confirmDelete({{ $sparePart->id }})">
                Smazat
            </flux:menu.item>
        @endcan
    </flux:menu>
</flux:dropdown>
