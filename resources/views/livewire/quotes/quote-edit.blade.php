<x-stellaris.livewire.forms.wrapper>
    <form id="contact-quote-form" wire:submit="submit">
            @include('livewire.quotes.partials.quote-form-inputs-without-files')

            <x-stellaris.livewire.forms.submit-strip>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    Uložit Poptávku
                </flux:button>
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
            </x-stellaris.livewire.forms.submit-strip>
    </form>
</x-stellaris.livewire.forms.wrapper>