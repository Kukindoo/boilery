@can('manageSpareParts', $work)
    <flux:dropdown position="bottom" align="end">
        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" :aria-label="__('Akce pro :name', ['name' => $part->name])" />
        <flux:menu>
            <flux:menu.item icon="pencil" wire:click="edit">
                Upravit množství
            </flux:menu.item>
            <flux:menu.separator />
            <flux:menu.item variant="danger" wire:click="remove" wire:confirm="Odebrat díl ze zakázky?">
                Odebrat
            </flux:menu.item>
        </flux:menu>
    </flux:dropdown>
@endcan
