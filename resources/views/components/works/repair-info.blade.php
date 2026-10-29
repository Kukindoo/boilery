@props(['work'])

<div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800/50 dark:shadow-none dark:inset-ring dark:inset-ring-white/10">
    <div class="px-4 py-6 sm:px-6 flex items-center justify-between">
        <h3 class="text-base/7 font-semibold text-gray-900 dark:text-white">
            Informace o opravě
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
                    Nahlášený problém
                </dt>
                <dd class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-300">
                    {{ $work->reported_problem ?? '—' }}
                </dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    Provedené práce
                </dt>
                <dd class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-300">
                    {{ $work->work_performed_description ?? '—' }}
                </dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    Poznámka
                </dt>
                <dd class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-300">
                    {{ $work->note ?? '—' }}
                </dd>
            </div>
        </dl>
    </div>
</div>
