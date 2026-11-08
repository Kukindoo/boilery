@can('manageSpareParts', $work)
    <flux:dropdown position="bottom" align="end">
        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" :aria-label="__('Akce pro :name', ['name' => $part->name])" />
        <flux:menu>
            <flux:menu.item variant="danger" wire:click="remove({{ $part->id }})" wire:confirm="Odebrat díl ze zakázky?">
                Odebrat
            </flux:menu.item>
        </flux:menu>
    </flux:dropdown>
@endcan
