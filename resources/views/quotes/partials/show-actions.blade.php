<flux:dropdown position="bottom" align="end">
    <flux:button size="sm" icon:trailing="chevron-down">Akce</flux:button>
    <flux:menu>
        <flux:modal.trigger name="pdf-preview">
            <flux:menu.item icon="document-text">Náhled Shrnutí poptávky</flux:menu.item>
        </flux:modal.trigger>

        @if( auth()->user()->can('accept', $quote) )
            <form method="POST" action="{{ route('quotes.accept', $quote) }}">
                @csrf
                <flux:menu.item type="submit" icon="check">
                    Přijmout poptávku
                </flux:menu.item>
            </form>
        @endif
    </flux:menu>
</flux:dropdown>
