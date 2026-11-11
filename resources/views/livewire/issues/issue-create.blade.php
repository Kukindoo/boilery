<form wire:submit="submit" class="space-y-6 col-span-3">
    @include('livewire.issues.partials.issue-inputs')
    <div class="flex gap-2">
        <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
            Vytvořit
        </flux:button>
        <flux:button :href="route('issues.index')">
            Zrušit
        </flux:button>
    </div>
</form>
