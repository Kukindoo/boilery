<x-stellaris.livewire.forms.side-to-side.section title="Informace o zákazníkovi">
    <x-stellaris.livewire.forms.side-to-side.field label="Jméno a příjmení">
        <flux:input wire:model="form.name" required autocomplete="name" />
        <flux:error name="form.name" />
    </x-stellaris.livewire.forms.side-to-side.field>

    <x-stellaris.livewire.forms.side-to-side.field label="E-mail">
        <flux:input wire:model="form.email" type="email" required autocomplete="email" />
        <flux:error name="form.email" />
    </x-stellaris.livewire.forms.side-to-side.field>

    <x-stellaris.livewire.forms.side-to-side.field label="Telefon">
        <flux:input wire:model="form.phone" type="tel" required autocomplete="tel" />
        <flux:error name="form.phone" />
    </x-stellaris.livewire.forms.side-to-side.field>

    <x-stellaris.livewire.forms.side-to-side.field label="Adresa">
        <flux:textarea wire:model="form.address" rows="3" autocomplete="street-address" />
        <flux:error name="form.address" />
    </x-stellaris.livewire.forms.side-to-side.field>
</x-stellaris.livewire.forms.side-to-side.section>
