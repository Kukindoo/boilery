@props(['work'])

<div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800/50 dark:shadow-none dark:inset-ring dark:inset-ring-white/10">
    <div class="px-4 py-6 sm:px-6 flex items-center justify-between">
        <h3 class="text-base/7 font-semibold text-gray-900 dark:text-white">
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
    <div class="border-t border-gray-100 dark:border-white/5">
        <dl class="divide-y divide-gray-100 dark:divide-white/5">
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    Typ výrobku
                </dt>
                <dd class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-300">
                    {{ $work->type_of_product ?? '—' }}
                </dd>
                <dt class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    Typové číslo
                </dt>
                <dd class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-300">
                    {{ $work->type_number ?? '—' }}
                </dd>
                <dt class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    Výrobní číslo
                </dt>
                <dd class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-300">
                    {{ $work->serial_number ?? '—' }}
                </dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    Datum nákupu
                </dt>
                <dd class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-300">
                    {{ $work->day_of_purchase?->format('d.m.Y') ?? '—' }}
                </dd>
                <dt class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    Typ budovy
                </dt>
                <dd class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-300">
                    {{ $work->building_type ?? '—' }}
                </dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    Oprava v záruce
                </dt>
                <dd class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-300">
                    {{ $work->repair_in_warranty ?? '—' }}
                </dd>
                <dt class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    Záruka uznána
                </dt>
                <dd class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-300">
                    {{ $work->warranty_accepted ? 'Ano' : 'Ne' }}
                </dd>
            </div>
        </dl>
    </div>
</div>
