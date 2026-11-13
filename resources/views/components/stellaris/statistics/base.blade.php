@props(['statistic'])

<flux:card class="flex h-full min-w-0 flex-col gap-5 bg-card-bg dark:bg-card-dark-bg">
    <flux:heading size="lg">{{ $statistic['heading'] }}</flux:heading>

    @if ($statistic['canView'])
        <dl class="flex flex-col gap-2">
            @foreach ($statistic['statuses'] as $status)
                <div class="flex items-center justify-between gap-3">
                    <dt>
                        <flux:badge :color="$status['colour']" size="sm">{{ $status['label'] }}</flux:badge>
                    </dt>
                    <dd class="font-medium tabular-nums text-body-text dark:text-muted-dark-text">{{ $status['count'] }}</dd>
                </div>
            @endforeach
        </dl>
    <flux:spacer />
        <div class="space-y-4">
            <div class="flex items-center justify-between gap-3">
                <dt>
                    <flux:badge color="grey" size="sm">Celkem</flux:badge>
                </dt>
                <dd class="font-medium tabular-nums text-body-text dark:text-muted-dark-text">{{ $statistic['total'] }}</dd>
            </div>
            <flux:link :href="route($statistic['route'])" class="mt-auto">
                {{ 'Zobrazit ' . $statistic['heading'] }}
            </flux:link>
        </div>
    @else
        <flux:text>Nemáte oprávnění k zobrazení statistik.</flux:text>
    @endif
</flux:card>