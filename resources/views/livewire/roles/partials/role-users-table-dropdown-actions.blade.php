@can('manageRoles', $user)
    <flux:dropdown position="bottom" align="end">
        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" :aria-label="__('Akce pro :name', ['name' => $user->name])" />
        <flux:menu>
            <flux:menu.item variant="danger" wire:click="confirmRemoveRole({{ $user->id }})">
                Odebrat roli
            </flux:menu.item>
        </flux:menu>
    </flux:dropdown>
@endcan
