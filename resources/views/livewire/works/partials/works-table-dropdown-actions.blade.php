<div class="flex items-center gap-2">
    <flux:dropdown>
        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal"/>
        <flux:menu>
            <flux:menu.item icon="eye" wire:click="openWork({{ $work->id }})">
                Otevřít
            </flux:menu.item>
            @can('update', $work)
                <flux:menu.separator/>
                <flux:menu.item icon="pencil" :href="route('works.edit', $work)">
                    Upravit
                </flux:menu.item>
                @canany(['start', 'end', 'cancel'], $work)
                    <flux:menu.submenu heading="Status">
                        @can('start', $work)
                            <flux:menu.item icon="check"
                                            wire:click="startWork({{ $work }})">
                                Zahájit
                            </flux:menu.item>
                        @endcan
                        @can('end', $work)
                            <flux:menu.item icon="check"
                                            wire:click="endWork({{ $work }})">
                                Dokončit
                            </flux:menu.item>
                        @endcan
                        @can('cancel', $work)
                            <flux:menu.item icon="x-circle"
                                            wire:click="cancelWork({{ $work }})">
                                Zrušit
                            </flux:menu.item>
                        @endcan
                    </flux:menu.submenu>
                @endcanany
            @endcan
            @can('delete', $work)
                <flux:menu.separator />
                <flux:modal.trigger :name="'delete-work-' . $work->id">
                    <flux:menu.item variant="danger">
                        Smazat
                    </flux:menu.item>
                </flux:modal.trigger>
            @endcan
        </flux:menu>
    </flux:dropdown>
    @can('delete', $work)
        <livewire:modals.deletion
            :model="$work"
            :key="'delete-works-'.$work->id"
            @deleted="resetPage"
            heading="Smazat zakázku?"
            text="Opravdu chcete smazat tuto zakázku? Zakázka bude archivovaná."
            :modal-name="'delete-work-' . $work->id"
        />
    @endcan
</div>
