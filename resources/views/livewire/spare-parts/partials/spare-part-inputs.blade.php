<flux:input wire:model="form.name" label="Název" required maxlength="255" />
<flux:input wire:model="form.partNumber" label="Číslo dílu" maxlength="255" />
<flux:textarea wire:model="form.description" label="Popis" />
<flux:field>
    <flux:label>
        Cena
    </flux:label>
    <flux:input.group>
        <flux:input.group.prefix>
            {{ config('price.currency') }}
        </flux:input.group.prefix>
        <flux:input wire:model="form.price" inputmode="decimal" placeholder="17,45" required />
    </flux:input.group>
    <flux:error name="form.price" />
</flux:field>
