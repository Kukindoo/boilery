<x-stellaris.livewire.forms.wrapper>
    <form id="contact-quote-form" wire:submit="submit">
            @include('livewire.quotes.partials.quote-form-inputs-without-files')

            <x-stellaris.livewire.forms.submit-strip>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    Uložit Poptávku
                </flux:button>
                <flux:button :href="route('quotes.index')">
                    Zrušit
                </flux:button>
            </x-stellaris.livewire.forms.submit-strip>
    </form>
</x-stellaris.livewire.forms.wrapper>