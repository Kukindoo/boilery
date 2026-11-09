<div>
    <flux:button size="sm" icon="plus" wire:click="open">
        Přidat úkon
    </flux:button>
    <flux:modal name="add-work-assembly-action" class="md:w-96">
        <form wire:submit="submit" class="space-y-6">
            <flux:heading size="lg">
                Přidat úkon k zakázce
            </flux:heading>
            <flux:select wire:model.live="form.assemblyActionId" variant="combobox" label="Pracovní úkon" placeholder="Hledejte podle názvu nebo kódu" empty="Žádné pracovní úkony nenalezeny.">
                @foreach ($availableActions as $action)
                    <flux:select.option :value="$action->id">
                        {{ $action->code !== '' ? $action->code.' – ' : '' }}{{ $action->name }}
                    </flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="form.price" label="Cena" inputmode="decimal" placeholder="0,00" required />
            @if ($availableActions->isEmpty())
                <flux:text>
                    Nejsou dostupné žádné další pracovní úkony.<br />
                    <flux:link :href="route('assembly-actions.index')">
                        Přejít na seznam pracovních úkonů
                    </flux:link>
                </flux:text>
            @endif
            <div class="flex justify-end gap-3">
                <flux:modal.close>
                    <flux:button>
                        Zrušit
                    </flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    Přidat úkon
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
