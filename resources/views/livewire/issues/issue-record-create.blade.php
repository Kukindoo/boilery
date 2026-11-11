<div>
    @can('update', $issue)
        @can('create', $type === 'quote' ? \App\Models\Quote::class : \App\Models\Work::class)
            <flux:button wire:click="create" wire:loading.attr="disabled">
                {{ $type === 'quote' ? 'Vytvořit poptávku' : 'Vytvořit zakázku' }}
            </flux:button>
        @endcan
    @endcan
</div>
