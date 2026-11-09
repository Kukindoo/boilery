<flux:table.row>
    <flux:table.cell>
        <flux:link :href="route('spare-parts.show', $part)">
            {{ $part->name }}
        </flux:link>
    </flux:table.cell>
    <flux:table.cell>
        {{ $part->part_number ?? '—' }}
    </flux:table.cell>
    <flux:table.cell>
        {{ $part->formattedPrice() }}
    </flux:table.cell>
    <flux:table.cell>
        @if ($editing && auth()->user()->can('manageSpareParts', $work))
            <form wire:submit="submit" class="space-y-2">
                <flux:input wire:model="form.amount" type="number" aria-label="Množství" min="1" max="65535" step="1" size="sm" required />
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
            {{ $amount }}
        @endif
    </flux:table.cell>
    <flux:table.cell>
        {{ $linePrice->format() }}
    </flux:table.cell>
    <flux:table.cell>
        @include('livewire.spare-parts.partials.work-spare-parts-table-dropdown-actions')
    </flux:table.cell>
</flux:table.row>
