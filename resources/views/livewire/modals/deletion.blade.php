<div>
    @if ($triggerLabel !== null)
        <flux:modal.trigger :name="$modalName ?? 'deletion-' . $this->getId()">
            <flux:button variant="danger" size="sm" wire:click="confirmDelete">
                {{ $triggerLabel }}
            </flux:button>
        </flux:modal.trigger>
    @endif
    <flux:modal :name="$modalName ?? 'deletion-' . $this->getId()" wire:model.self="show" @close="close">
        <div class="space-y-4">
            <flux:heading>
                {{ $heading }}
            </flux:heading>
            <flux:text>
                {{ $text }}
            </flux:text>
            <div class="flex justify-end gap-2">
                <flux:button variant="danger" wire:click="delete" wire:loading.attr="disabled" wire:target="delete">
                    {{ $confirmLabel }}
                </flux:button>
                <flux:modal.close>
                    <flux:button>
                        {{ $cancelLabel }}
                    </flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</div>
