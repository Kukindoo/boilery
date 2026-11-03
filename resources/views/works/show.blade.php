<x-layouts::app :title="__('Zakázky')">
    <div class="space-y-6">
        <x-stellaris.container.model-card
                :heading="__('Zakázka :order_id', ['order_id' => $work->order_id])"
                :subheading="$work->user?->name ?? '—'">
            <x-slot name="actions">
                <div>
                    @can('update', $work)
                        <flux:button size="sm" icon="pencil" :href="route('works.edit', $work)" class="inline-block">
                            Upravit
                        </flux:button>
                    @endcan

                    @if( auth()->user()->can('start', $work) )
                        <form method="POST" action="{{ route('works.start', $work) }}" class="inline-block">
                            @csrf
                            <flux:button type="submit" icon="check" size="sm">
                                Zahájit zakázku
                            </flux:button>
                        </form>
                    @endif

                    @if( auth()->user()->can('end', $work) )
                        <form method="POST" action="{{ route('works.end', $work) }}" class="inline-block">
                            @csrf
                            <flux:button type="submit" icon="check" size="sm">
                                Dokončit zakázku
                            </flux:button>
                        </form>
                        <form method="POST" action="{{ route('works.cancel', $work) }}" class="inline-block">
                            @csrf
                            <flux:button type="submit" icon="x-mark" size="sm">
                                Zrušit zakázku
                            </flux:button>
                        </form>
                    @endif
                </div>
            </x-slot>

            <x-works.work-info :work="$work"/>
            <x-works.work-boiler-info :work="$work"/>
            <x-works.work-repair-info :work="$work"/>
            <x-works.work-transport-info :work="$work"/>
            <x-works.work-attached-images :work="$work"/>

        </x-stellaris.container.model-card>
    </div>
</x-layouts::app>
