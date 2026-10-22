<flux:card>
    <div class="flex items-center justify-between gap-4">
        <flux:heading>Zakázky</flux:heading>
        @can('create', \App\Models\Work::class)
            <flux:button size="sm" :href="route('works.create')">Přidat zakázku</flux:button>
        @endcan
    </div>

    <flux:input wire:model.live.debounce.300ms="search" label="Hledat zakázky" />

    <flux:table bleed :paginate="$this->works">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortBy === 'order_id'" :direction="$sortDirection" wire:click="sort('order_id')">Zakázka</flux:table.column>
            <flux:table.column>Jméno a příjmení</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'type_of_product'" :direction="$sortDirection" wire:click="sort('type_of_product')">Typ výrobku</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'work_accepted_at'" :direction="$sortDirection" wire:click="sort('work_accepted_at')">Přijato</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'work_completed_at'" :direction="$sortDirection" wire:click="sort('work_completed_at')">Dokončeno</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->works as $work)
                <flux:table.row :key="$work->id">
                    <flux:table.cell>
                        <flux:link wire:click="openWork({{ $work->id }})" class="cursor-pointer">{{ $work->order_id }}</flux:link>
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">{{ $work->user?->name ?? 'Neznámý' }}</flux:table.cell>
                    <flux:table.cell variant="strong">{{ $work->type_of_product ?? 'Neznámý' }}</flux:table.cell>
                    <flux:table.cell>{{ $work->work_accepted_at?->format('d.m.Y') ?? '—' }}</flux:table.cell>
                    <flux:table.cell>{{ $work->work_completed_at?->format('d.m.Y') ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="py-0">
                        <flux:dropdown>
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                            <flux:menu>
                                <flux:menu.item icon="eye" wire:click="openWork({{ $work->id }})">Otevřít</flux:menu.item>
                                @can('update', $work)
                                    <flux:menu.item :href="route('works.edit', $work)">Upravit</flux:menu.item>
                                @endcan
                                @can('delete', $work)
                                    <flux:menu.item variant="danger" wire:click="confirmDelete({{ $work->id }})">Smazat</flux:menu.item>
                                @endcan
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6">Žádné zakázky.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
    <flux:modal wire:model="confirmingDeletion">
        <div class="space-y-4">
            <flux:heading>Smazat zakázku?</flux:heading>
            <flux:text>Zakázka bude archivována.</flux:text>
            <flux:button variant="danger" wire:click="delete" wire:loading.attr="disabled">Smazat zakázku</flux:button>
            <flux:button wire:click="$set('confirmingDeletion', false)">Zrušit</flux:button>
        </div>
    </flux:modal>
</flux:card>
