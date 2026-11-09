@can('manageSpareParts', $work)
    <flux:dropdown position="bottom" align="end">
        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" :aria-label="__('Akce pro :name', ['name' => $part->name])" />
        <flux:menu>
            <flux:menu.item icon="pencil" wire:click="edit">
                Upravit množství
            </flux:menu.item>
            <flux:menu.separator />
            <flux:modal.trigger :name="'detach-spare-part-' . $work->id . '-' . $part->id">
                <flux:menu.item variant="danger" wire:click="confirmDelete">
                    Odebrat
                </flux:menu.item>
            </flux:modal.trigger>
        </flux:menu>
    </flux:dropdown>

    <livewire:modals.deletion
            wire:model="confirmingDeletion"
            @confirmed="delete"
            :key="'detach-spare-part-' . $work->id . '-' . $part->id"
            heading="Odebrat náhradní díl?"
            text="Opravdu chcete odebrat tento náhradní díl ze zakázky?"
            :modal-name="'detach-spare-part-' . $work->id . '-' . $part->id"
            confirm-label="Odebrat"
    />
@endcan
