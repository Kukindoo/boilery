<flux:modal wire:model="confirmingDeletion">
    <div class="space-y-4">
        <flux:heading>
            {{ $heading ?? 'Smazat?' }}
        </flux:heading>
        <flux:text>
            {{ $text ?? '' }}
        </flux:text>
        <flux:button variant="danger" wire:click="delete" wire:loading.attr="disabled">
            {{ $button ?? 'Smazat' }}
        </flux:button>
        <flux:button wire:click="$set('confirmingDeletion', false)">
            {{ $cancel ?? 'Zrušit' }}
        </flux:button>
    </div>
</flux:modal>