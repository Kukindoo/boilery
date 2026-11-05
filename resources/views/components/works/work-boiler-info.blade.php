@props(['work'])

<div class="overflow-hidden bg-card-bg shadow-sm sm:rounded-lg dark:bg-card-dark-bg dark:shadow-none dark:inset-ring dark:inset-ring-primary-dark-border/10">
    <div class="px-4 py-6 sm:px-6 flex items-center justify-between">
        <h3 class="text-base/7 font-semibold text-heading-text dark:text-primary-dark-text">
            Informace o bojleru
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
                    Výrobce bojleru
                </dt>
                <dd class="mt-1 text-sm/6 text-body-text sm:col-span-2 sm:mt-0 dark:text-muted-dark-text">
                    {{ $work->boiler_manufacturer?->label() ?? '—' }}
                </dd>
                <dt class="text-sm font-medium text-heading-text dark:text-secondary-dark-text">
                    Typ výrobku
                </dt>
                <dd class="mt-1 text-sm/6 text-body-text sm:col-span-2 sm:mt-0 dark:text-muted-dark-text">
                    {{ $work->type_of_product ?? '—' }}
                </dd>
                <dt class="text-sm font-medium text-heading-text dark:text-secondary-dark-text">
                    Typové číslo
                </dt>
                <dd class="mt-1 text-sm/6 text-body-text sm:col-span-2 sm:mt-0 dark:text-muted-dark-text">
                    {{ $work->type_number ?? '—' }}
                </dd>
                <dt class="text-sm font-medium text-heading-text dark:text-secondary-dark-text">
                    Výrobní číslo
                </dt>
                <dd class="mt-1 text-sm/6 text-body-text sm:col-span-2 sm:mt-0 dark:text-muted-dark-text">
                    {{ $work->serial_number ?? '—' }}
                </dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-heading-text dark:text-secondary-dark-text">
                    Datum nákupu
                </dt>
                <dd class="mt-1 text-sm/6 text-body-text sm:col-span-2 sm:mt-0 dark:text-muted-dark-text">
                    {{ $work->day_of_purchase?->format('d.m.Y') ?? '—' }}
                </dd>
                <dt class="text-sm font-medium text-heading-text dark:text-secondary-dark-text">
                    Typ budovy
                </dt>
                <dd class="mt-1 text-sm/6 text-body-text sm:col-span-2 sm:mt-0 dark:text-muted-dark-text">
                    {{ $work->building_type ?? '—' }}
                </dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-heading-text dark:text-secondary-dark-text">
                    Oprava v záruce
                </dt>
                <dd class="mt-1 text-sm/6 text-body-text sm:col-span-2 sm:mt-0 dark:text-muted-dark-text">
                    {{ $work->repair_in_warranty ?? '—' }}
                </dd>
                <dt class="text-sm font-medium text-heading-text dark:text-secondary-dark-text">
                    Záruka uznána
                </dt>
                <dd class="mt-1 text-sm/6 text-body-text sm:col-span-2 sm:mt-0 dark:text-muted-dark-text">
                    {{ $work->warranty_accepted ? 'Ano' : 'Ne' }}
                </dd>
            </div>
        </dl>
    </div>
</div>
