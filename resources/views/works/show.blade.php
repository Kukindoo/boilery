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
                <x-works.work-boiler-info :work="$work"/>
                <x-works.work-repair-info :work="$work"/>
                <x-works.work-transport-info :work="$work"/>
                <x-works.work-attached-images :work="$work"/>

            </x-stellaris.container.model-card>
        </div>
</x-layouts::app>
