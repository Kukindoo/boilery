<flux:dropdown>
    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal">
    </flux:button>
    <flux:menu>
        <flux:menu.item icon="eye" wire:click="openQuote({{ $quote }})">
            Otevřít
        </flux:menu.item>
        @can('update', $quote)
            <flux:menu.separator/>
            <flux:menu.submenu heading="Status">
                <flux:menu.item icon="check"
                                wire:click="acceptQuote({{ $quote }})">
                    Přijmout
                </flux:menu.item>
                <flux:menu.item icon="phone-arrow-up-right"
                                wire:click="changeQuoteStatus({{ $quote }}, '{{ \App\Enums\QuoteStatus::CONTACTED }}')">
                    Kontaktovaný
                </flux:menu.item>
                <flux:menu.item icon="x-circle"
                                wire:click="changeQuoteStatus({{ $quote }}, '{{ \App\Enums\QuoteStatus::REJECTED }}')">
                    Odmítnout
                </flux:menu.item>
            </flux:menu.submenu>
        @endcan
        @can('delete', $quote)
            <flux:menu.separator/>
            <flux:menu.item variant="danger" icon="trash" disabled>
                Smazat
            </flux:menu.item>
        @endcan
    </flux:menu>
</flux:dropdown>