<x-stellaris.livewire.table.wrapper heading="Zakázky">
    <x-slot name="actions">
        @include('livewire.works.partials.works-table-actions')
    </x-slot>
    <x-slot name="filters">
        @include('livewire.works.partials.works-table-filters')
    </x-slot>
    <flux:table bleed :paginate="$this->works">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortBy === 'order_id'" :direction="$sortDirection"
                               wire:click="sort('order_id')">
                Zakázka
            </flux:table.column>
            <flux:table.column>
                Jméno a příjmení
            </flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'type_of_product'" :direction="$sortDirection"
                               wire:click="sort('type_of_product')">
                Typ výrobku
            </flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'work_accepted_at'" :direction="$sortDirection"
                               wire:click="sort('work_accepted_at')">
                Přijato
            </flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'work_completed_at'" :direction="$sortDirection"
                               wire:click="sort('work_completed_at')">
                Dokončeno
            </flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->works as $work)
                <flux:table.row :key="$work->id">
                    <flux:table.cell>
                        <flux:link wire:click="openWork({{ $work->id }})" class="cursor-pointer">
                            {{ $work->order_id }}
                        </flux:link>
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">
                        {{ $work->user?->name ?? 'Neznámý' }}
                    </flux:table.cell>
                    <flux:table.cell variant="strong">
                        {{ $work->type_of_product ?? 'Neznámý' }}
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $work->work_accepted_at?->format('d.m.Y') ?? '—' }}
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $work->work_completed_at?->format('d.m.Y') ?? '—' }}
                    </flux:table.cell>
                    <flux:table.cell class="py-0">
                        @include('livewire.works.partials.works-table-dropdown-actions')
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6">
                        Žádné zakázky.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
    @include('livewire.modals.deletion', [
        'heading' => 'Smazat zakázku?',
        'text' => 'Opravdu chcete smazat tuto zakázku? Zakázka bude archivovaná.',
        'button' => 'Smazat',
        'cancel' => 'Zrušit',
    ])
</x-stellaris.livewire.table.wrapper>
