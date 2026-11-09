@can('manageAssemblyActions', $work)
    <flux:dropdown position="bottom" align="end">
        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" :aria-label="__('Akce pro :name', ['name' => $action->name])" />
        <flux:menu>
            <flux:menu.item icon="pencil" wire:click="edit">
                Upravit cenu
            </flux:menu.item>
            <flux:menu.separator />
            <flux:menu.item variant="danger" wire:click="confirmDelete">
                Odebrat
            </flux:menu.item>
        </flux:menu>
    </flux:dropdown>
    @include('livewire.modals.deletion', [
        'heading' => 'Odebrat pracovní úkon?',
        'text' => 'Opravdu chcete odebrat tento pracovní úkon ze zakázky?',
        'button' => 'Odebrat',
        'cancel' => 'Zrušit',
    ])
@endcan
