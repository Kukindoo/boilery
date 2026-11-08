<form wire:submit="submit" class="space-y-6">
    @include('livewire.spare-parts.partials.spare-part-inputs')
    <div class="flex flex-wrap gap-3">
        <flux:button type="submit" variant="primary" wire:loading.attr="disabled">Uložit</flux:button>
        <flux:button :href="route('spare-parts.index')">Zrušit</flux:button>
    </div>
</form>
