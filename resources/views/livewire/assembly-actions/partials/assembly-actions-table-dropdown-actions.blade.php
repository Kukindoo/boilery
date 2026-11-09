<div class="flex items-center gap-2">
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
                <flux:modal.trigger :name="'delete-assembly-action-' . $assemblyAction->id">
                    <flux:menu.item variant="danger">
                        Smazat
                    </flux:menu.item>
                </flux:modal.trigger>
            @endcan
        </flux:menu>
    </flux:dropdown>
    @can('delete', $assemblyAction)
        <livewire:modals.deletion
            :model="$assemblyAction"
            :key="'delete-assembly-actions-'.$assemblyAction->id"
            @deleted="resetPage"
            heading="Smazat pracovní úkon?"
            text="Opravdu chcete smazat tento pracovní úkon? Úkon bude archivován."
            :modal-name="'delete-assembly-action-' . $assemblyAction->id"
        />
    @endcan
</div>
