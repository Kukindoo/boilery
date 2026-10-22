<div class="px-6">
    <form wire:submit="submit">
        @include('livewire.works.partials.work-form-inputs')

        <div class="flex flex-wrap gap-3">
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                Uložit zakázku
            </flux:button>
            <flux:button :href="route('works.index')">
                Zrušit
            </flux:button>
        </div>
    </form>
</div>