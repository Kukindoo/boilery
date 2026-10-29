    <x-layouts::app :title="__('Zakázky')">
        <div class="space-y-6">
            <x-stellaris.container.model-card
                    :heading="__('Zakázka :order_id', ['order_id' => $work->order_id])"
                    :subheading="$work->user?->name ?? '—'">
                @can('update', $work)
                    <x-slot name="actions">
                        <flux:button size="sm" :href="route('works.edit', $work)">
                            Upravit
                        </flux:button>
                    </x-slot>
                @endcan

                <x-works.work-info :work="$work"/>
                <x-works.boiler-info :work="$work"/>
                <x-works.repair-info :work="$work"/>
                <x-works.transport-info :work="$work"/>

            </x-stellaris.container.model-card>
            @if ($work->quote)
                @can('view', $work->quote)
                    <x-stellaris.container.model-card
                            :heading="__('Poptávka :quote_id', ['quote_id' => $work->quote->id])">
                        <x-quotes.quote-person-info :quote="$work->quote"/>
                        <x-quotes.quote-boiler-info :quote="$work->quote"/>
                        <x-quotes.quote-attachments :quote="$work->quote"/>
                    </x-stellaris.container.model-card>
                @endcan
            @endif
        </div>
</x-layouts::app>
