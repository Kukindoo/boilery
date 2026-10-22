<div class="px-6">
    <form id="contact-quote-form" wire:submit="submit">
        <div class="mx-auto max-w-xl lg:mr-0 lg:max-w-lg">
            @include('livewire.quotes.partials.quote-form-inputs-without-files')

            <div class="mt-8 flex justify-end">
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    Uložit Poptávku
                </flux:button>
                <flux:button :href="route('quotes.index')">
                    Zrušit
                </flux:button>
            </div>
        </div>
    </form>
</div>