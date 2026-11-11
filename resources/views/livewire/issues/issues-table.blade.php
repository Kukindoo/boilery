<x-stellaris.livewire.table.wrapper :heading="$customer === null ? 'Případy' : 'Případy zákazníka'">
    <x-slot name="actions">
        @include('livewire.issues.partials.issues-table-actions')
    </x-slot>
    <x-slot name="filters">
        @include('livewire.issues.partials.issues-table-filters')
    </x-slot>
    <flux:pagination :paginator="$this->issues" />
    <flux:table :paginate="$this->issues">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortBy === 'id'" :direction="$sortDirection" wire:click="sort('id')">
                ID
            </flux:table.column>
            <flux:table.column>
                {{ $customer === null ? 'Zákazník' : 'Vytvořeno' }}
            </flux:table.column>
            <flux:table.column>
                Status
            </flux:table.column>
            <flux:table.column>
                Akce
            </flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->issues as $issue)
                <flux:table.row :key="$issue->id">
                    <flux:table.cell>
                        @can('view', $issue)
                            <flux:link :href="route('issues.show', $issue)">
                                Případ #{{ $issue->id }}
                            </flux:link>
                        @else
                            Případ #{{ $issue->id }}
                        @endcan
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $customer === null ? ($issue->user?->name ?? '—') : ($issue->created_at?->format('d-m-Y H:i') ?? '—') }}
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$issue->status->colour()" size="sm">
                            {{ $issue->status->label() }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        @canany(['view', 'update', 'delete'], $issue)
                            @include('livewire.issues.partials.issues-table-dropdown-actions')
                        @endcanany
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4">
                        Žádné případy.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</x-stellaris.livewire.table.wrapper>
