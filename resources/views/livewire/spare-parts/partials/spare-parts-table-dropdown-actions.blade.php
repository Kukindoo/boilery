<div class="flex items-center gap-2">
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
                <flux:modal.trigger :name="'delete-spare-part-' . $sparePart->id">
                    <flux:menu.item variant="danger">
                        Smazat
                    </flux:menu.item>
                </flux:modal.trigger>
            @endcan
        </flux:menu>
    </flux:dropdown>
    @can('delete', $sparePart)
        <livewire:modals.deletion
            :model="$sparePart"
            :key="'delete-spare-parts-'.$sparePart->id"
            @deleted="resetPage"
            heading="Smazat náhradní díl?"
            text="Opravdu chcete smazat tento náhradní díl? Náhradní díl bude archivován."
            :modal-name="'delete-spare-part-' . $sparePart->id"
        />
    @endcan
</div>
