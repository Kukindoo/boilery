@can('manageAssemblyActions', $work)
    <div class="flex items-center gap-2">
        <flux:dropdown position="bottom" align="end">
            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" :aria-label="__('Akce pro :name', ['name' => $action->name])" />
            <flux:menu>
                <flux:menu.item icon="pencil" wire:click="edit">
                    Upravit cenu
                </flux:menu.item>
                <flux:menu.separator />
                <flux:modal.trigger :name="'detach-assembly-action-' . $work->id . '-' . $action->id">
                    <flux:menu.item variant="danger" wire:click="confirmDelete">
                        Odebrat
                    </flux:menu.item>
                </flux:modal.trigger>
            </flux:menu>
        </flux:dropdown>
        <livewire:modals.deletion
            wire:model="confirmingDeletion"
            @confirmed="delete"
            :key="'detach-assembly-action-' . $work->id . '-' . $action->id"
            heading="Odebrat pracovní úkon?"
            text="Opravdu chcete odebrat tento pracovní úkon ze zakázky?"
            :modal-name="'detach-assembly-action-' . $work->id . '-' . $action->id"
            confirm-label="Odebrat"
        />
    </div>
@endcan
