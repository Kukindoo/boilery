<flux:dropdown>
    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
    <flux:menu>
        <flux:menu.item icon="eye" wire:click="openWork({{ $work->id }})">
            Otevřít
        </flux:menu.item>
        @can('update', $work)
            <flux:menu.item :href="route('works.edit', $work)">
                Upravit
            </flux:menu.item>
        @endcan
        @can('delete', $work)
            <flux:menu.item variant="danger" wire:click="confirmDelete({{ $work->id }})">
                Smazat
            </flux:menu.item>
        @endcan
    </flux:menu>
</flux:dropdown>