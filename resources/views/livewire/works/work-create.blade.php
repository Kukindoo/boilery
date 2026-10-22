<form wire:submit="submit" class="space-y-6">
    @include('livewire.works.partials.work-form-inputs')
    <div class="flex gap-3">
        <flux:button type="submit" variant="primary" wire:loading.attr="disabled">Uložit zakázku</flux:button>
        <flux:button :href="route('works.index')">Zrušit</flux:button>
    </div>
</form>
