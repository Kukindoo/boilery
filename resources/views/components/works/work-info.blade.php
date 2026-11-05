@props(['work'])

<div class="overflow-hidden bg-card-bg shadow-sm sm:rounded-lg dark:bg-card-dark-bg dark:shadow-none dark:inset-ring dark:inset-ring-primary-dark-border/10">
    <div class="px-4 py-6 sm:px-6 flex items-center justify-between">
        <h3 class="text-base/7 font-semibold text-gray-900 dark:text-white">
            Informace o Zakázce
        </h3>
        <div class="ml-4 shrink-0 print:hidden">
            @can('update', $work)
                <flux:button size="sm" :href="route('works.edit', $work)">
                    Upravit
                </flux:button>
            @endcan
        </div>
    </div>
    <div class="border-t border-subtle-border dark:border-primary-dark-border/5">
        <dl class="divide-y divide-subtle-border dark:divide-primary-dark-border/5">
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-heading-text dark:text-secondary-dark-text">
                    Status
                </dt>
                <dd class="mt-1 text-sm/6 text-body-text sm:col-span-2 sm:mt-0 dark:text-muted-dark-text">
                    <flux:badge size="sm" :color="$work->status->colour()">
                        {{ $work->status->label() }}
                    </flux:badge>
                </dd>
                <dt class="text-sm font-medium text-heading-text dark:text-secondary-dark-text">
                    Zákazník
                </dt>
                <dd class="mt-1 text-sm/6 text-body-text sm:col-span-2 sm:mt-0 dark:text-muted-dark-text">
                    <x-customers.customer-name :customer="$work->user" />
                </dd>
                <dt class="text-sm font-medium text-heading-text dark:text-secondary-dark-text">
                    Poptávka
                </dt>
                <dd class="mt-1 text-sm/6 text-body-text sm:col-span-2 sm:mt-0 dark:text-muted-dark-text">
                    @if ($work->quote && auth()->user()->can('view', $work->quote))
                        <flux:link :href="route('quotes.show', $work->quote)">
                            {{ $work->quote_id }}
                        </flux:link>
                    @else
                        —
                    @endif
                </dd>
                <dt class="text-sm font-medium text-heading-text dark:text-secondary-dark-text">
                    Datum přijetí
                </dt>
                <dd class="mt-1 text-sm/6 text-body-text sm:col-span-2 sm:mt-0 dark:text-muted-dark-text">
                    {{ $work->work_accepted_at?->format('d.m.Y') ?? '—' }}
                </dd>
                <dt class="text-sm font-medium text-heading-text dark:text-secondary-dark-text">
                    Datum zahájení
                </dt>
                <dd class="mt-1 text-sm/6 text-body-text sm:col-span-2 sm:mt-0 dark:text-muted-dark-text">
                    {{ $work->work_started_at?->format('d.m.Y') ?? '—' }}
                </dd>
                <dt class="text-sm font-medium text-heading-text dark:text-secondary-dark-text">
                    Datum dokončení
                </dt>
                <dd class="mt-1 text-sm/6 text-body-text sm:col-span-2 sm:mt-0 dark:text-muted-dark-text">
                    {{ $work->work_completed_at?->format('d.m.Y') ?? '—' }}
                </dd>
                <dt class="text-sm font-medium text-heading-text dark:text-secondary-dark-text">
                    Jméno technika
                </dt>
                <dd class="mt-1 text-sm/6 text-body-text sm:col-span-2 sm:mt-0 dark:text-muted-dark-text">
                    {{ $work->mechanic_name ?? '—' }}
                </dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
            </div>
        </dl>
    </div>
</div>
