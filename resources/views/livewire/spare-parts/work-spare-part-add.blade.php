<div>
    <flux:button size="sm" icon="plus" wire:click="open">
        Přidat díl
    </flux:button>
    <flux:modal name="add-work-spare-part" class="md:w-96">
        <form wire:submit="submit" class="space-y-6">
            <flux:heading size="lg">
                Přidat díl k zakázce
            </flux:heading>
            <flux:select wire:model.live="form.sparePartId" variant="listbox" label="Náhradní díl" placeholder="Vyberte náhradní díl">
                @foreach ($availableParts as $part)
                    <flux:select.option :value="$part->id">
                        {{ $part->name }}{{ $part->part_number ? ' ('.$part->part_number.')' : '' }}
                    </flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="form.amount" type="number" label="Množství" min="1" max="65535" step="1" required />
            @if ($availableParts->isEmpty())
                <flux:text>
                    Nejsou dostupné žádné další náhradní díly.
                </flux:text>
            @endif
            <div class="flex justify-end gap-3">
                <flux:modal.close>
                    <flux:button>
                        Zrušit
                    </flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    Přidat díl
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
