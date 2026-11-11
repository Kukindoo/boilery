<div class="flex items-center gap-2">
    <flux:dropdown position="bottom" align="end">
        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" :aria-label="__('Akce pro :name', ['name' => 'Případ #' . $issue->id])" />
        <flux:menu>
            <flux:menu.item icon="eye" :href="route('issues.show', $issue)">
                Detail
            </flux:menu.item>
            @can('update', $issue)
                <flux:menu.item icon="pencil" :href="route('issues.edit', $issue)">
                    Upravit
                </flux:menu.item>
            @endcan
            @can('delete', $issue)
                <flux:menu.separator />
                <flux:modal.trigger :name="'delete-issue-' . $issue->id">
                    <flux:menu.item variant="danger">
                        Smazat
                    </flux:menu.item>
                </flux:modal.trigger>
            @endcan
        </flux:menu>
    </flux:dropdown>
    @can('delete', $issue)
        <livewire:modals.deletion
            :model="$issue"
            :key="'delete-issues-'.$issue->id"
            @deleted="resetPage"
            heading="Smazat případ?"
            text="Opravdu chcete smazat tento případ? Případ bude archivován."
            :modal-name="'delete-issue-' . $issue->id"
        />
    @endcan
</div>
