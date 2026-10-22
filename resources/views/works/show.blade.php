<x-layouts::app :title="__('Zakázky')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex items-center justify-between gap-4">
            <flux:heading>
                Zakázka {{ $work->order_id }}
                <flux:text>{{ $work->user?->name ?? '—' }}</flux:text>
            </flux:heading>
                @can('update', $work)
                    <flux:button size="sm" :href="route('works.edit', $work)">
                        Upravit
                    </flux:button>
                @endcan
        </div>

        <x-works.work-info :work="$work" />
        <x-works.boiler-info :work="$work" />
        <x-works.repair-info :work="$work" />
        <x-works.transport-info :work="$work" />
    </div>
</x-layouts::app>
