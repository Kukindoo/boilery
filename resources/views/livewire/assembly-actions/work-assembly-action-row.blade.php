<flux:table.row>
    <flux:table.cell>
        {{ $action->code }}
    </flux:table.cell>
    <flux:table.cell>
        @can('view', $action)
            <flux:link :href="route('assembly-actions.show', $action)">
                {{ $action->name }}
            </flux:link>
        @else
            {{ $action->name }}
        @endcan
    </flux:table.cell>
    <flux:table.cell>
        @if ($editing && auth()->user()->can('manageAssemblyActions', $work))
            <form wire:submit="submit" class="space-y-2">
                <flux:input wire:model="form.price" aria-label="Cena" inputmode="decimal" size="sm" required />
                <div class="flex gap-2">
                    <flux:button type="submit" size="sm" variant="primary" wire:loading.attr="disabled">
                        Uložit
                    </flux:button>
                    <flux:button type="button" size="sm" wire:click="cancel">
                        Zrušit
                    </flux:button>
                </div>
            </form>
        @else
            {{ $price->format() }}
        @endif
    </flux:table.cell>
    <flux:table.cell>
        @include('livewire.assembly-actions.partials.work-assembly-actions-table-dropdown-actions')
    </flux:table.cell>
</flux:table.row>
