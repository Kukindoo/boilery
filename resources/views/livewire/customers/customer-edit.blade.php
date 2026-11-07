<x-stellaris.livewire.forms.wrapper>
    <form wire:submit="submit">
        @include('livewire.customers.partials.customer-form-inputs')

        <x-stellaris.livewire.forms.submit-strip>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                Uložit zákazníka
            </flux:button>
            <flux:button :href="route('customers.show', $form->customer)">
                Zrušit
            </flux:button>
        </x-stellaris.livewire.forms.submit-strip>
    </form>
</x-stellaris.livewire.forms.wrapper>
