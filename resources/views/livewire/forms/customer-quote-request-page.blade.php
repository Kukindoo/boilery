<div class="px-6 pt-20 pb-24 sm:pb-32 lg:px-8 lg:py-48">
    @if(! $submitted)
        <form id="contact-quote-form" wire:submit="submit">
            <div class="mx-auto max-w-xl lg:mr-0 lg:max-w-lg">
                @include('livewire.quotes.partials.quote-form-inputs')

                <div class="mt-8 flex justify-end">
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                        Odeslat
                    </flux:button>
                </div>
            </div>
        </form>
    @else
        <x-contacts.contact-success-message/>
    @endif
</div>

