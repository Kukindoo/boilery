<x-stellaris.livewire.forms.wrapper>
    <form id="work-edit-form" wire:submit="submit">
            @include('livewire.works.partials.work-form-inputs')

            <x-stellaris.livewire.forms.submit-strip>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    Uložit zakázku
                </flux:button>
                <flux:button :href="route('works.index')">
                    Zrušit
                </flux:button>
            </x-stellaris.livewire.forms.submit-strip>
    </form>
</x-stellaris.livewire.forms.wrapper>