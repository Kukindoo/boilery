<form wire:submit="submit" class="space-y-6 col-span-3">
    @include('livewire.assembly-actions.partials.assembly-action-inputs')
    <x-stellaris.livewire.forms.submit-strip>
        <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
            Uložit
        </flux:button>
        <flux:button :href="route('assembly-actions.index')">
            Zrušit
        </flux:button>
    </x-stellaris.livewire.forms.submit-strip>
</form>
