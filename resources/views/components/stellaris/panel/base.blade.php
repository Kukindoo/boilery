<div class="overflow-hidden bg-card-bg shadow-sm sm:rounded-lg dark:bg-card-dark-bg dark:shadow-none dark:inset-ring dark:inset-ring-primary-dark-border/10 px-2 py-2">
    @if(($header ?? false) || ($action ?? false))
        <div class="px-4 py-6 sm:px-6 flex items-center justify-between">
            <h3 class="text-base/7 font-semibold text-gray-900 dark:text-white">
                {{ $header ?? '' }}
            </h3>
            <div class="ml-4 shrink-0 print:hidden">
                {{ $action ?? '' }}
            </div>
        </div>
    @endif

    {{ $slot }}
</div>