@php
    use App\Enums\WorkStatus;
@endphp

<flux:dropdown position="bottom" align="end">
    <flux:button size="sm" icon:trailing="chevron-down">Akce</flux:button>
    <flux:menu>
        <flux:modal.trigger name="pdf-preview">
            <flux:menu.item icon="document-text">Náhled Servisního protokolu</flux:menu.item>
        </flux:modal.trigger>

        @can('update', $work)
            <flux:menu.item icon="pencil" :href="route('works.edit', $work)">
                Upravit
            </flux:menu.item>
        @endcan

        @if( auth()->user()->can('start', $work) )
            <form method="POST" action="{{ route('works.start', $work) }}">
                @csrf
                <flux:menu.item type="submit" icon="check">
                    {{ $work->status === WorkStatus::NEW ? 'Zahájit zakázku' : 'Restartovat zakázku' }}
                </flux:menu.item>
            </form>
        @endif

        @if( auth()->user()->can('end', $work) )
            <form method="POST" action="{{ route('works.end', $work) }}">
                @csrf
                <flux:menu.item type="submit" icon="check">
                    Dokončit zakázku
                </flux:menu.item>
            </form>
            <form method="POST" action="{{ route('works.cancel', $work) }}">
                @csrf
                <flux:menu.item type="submit" icon="x-mark">
                    Zrušit zakázku
                </flux:menu.item>
            </form>
        @endif
    </flux:menu>
</flux:dropdown>
