<x-layouts::app :title="__('Zakázky')">
    <div class="space-y-6">
        <x-stellaris.container.index-card
                :heading="__('Zakázka :order_id', ['order_id' => $work->order_id])">
            <x-slot name="subheading">
                <x-customers.customer-name :customer="$work->user"/>
            </x-slot>

            <x-slot name="actions">
                <x-pdfs.pdf-modal-preview :model="$work"
                                          :pdf-content="$pdfContent"
                                          :file-type="\App\Enums\FileTypes::SERVICE_PROTOCOL->value"/>

                @can('update', $work)
                    <flux:button size="sm" icon="pencil" :href="route('works.edit', $work)">
                        Upravit
                    </flux:button>
                @endcan

                @if( auth()->user()->can('start', $work) )
                    <form method="POST" action="{{ route('works.start', $work) }}">
                        @csrf
                        <flux:button type="submit" icon="check" size="sm">
                            Zahájit zakázku
                        </flux:button>
                    </form>
                @endif

                @if( auth()->user()->can('end', $work) )
                    <form method="POST" action="{{ route('works.end', $work) }}">
                        @csrf
                        <flux:button type="submit" icon="check" size="sm">
                            Dokončit zakázku
                        </flux:button>
                    </form>
                    <form method="POST" action="{{ route('works.cancel', $work) }}">
                        @csrf
                        <flux:button type="submit" icon="x-mark" size="sm">
                            Zrušit zakázku
                        </flux:button>
                    </form>
                @endif
            </x-slot>

            <x-works.work-info :work="$work"/>
            <x-works.work-boiler-info :work="$work"/>
            <x-works.work-repair-info :work="$work"/>
            <x-works.work-transport-info :work="$work"/>
            <x-files.attachment-card :model="$work"/>

        </x-stellaris.container.index-card>
    </div>
</x-layouts::app>
