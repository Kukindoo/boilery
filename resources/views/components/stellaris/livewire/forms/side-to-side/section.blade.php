<section
        class="overflow-hidden bg-card-bg shadow-sm sm:rounded-lg dark:bg-card-dark-bg dark:shadow-none dark:inset-ring dark:inset-ring-primary-dark-border/10">
    <div class="px-4 py-6 sm:px-6">
        <h3 class="text-base/7 font-semibold text-heading-text dark:text-primary-dark-text">
            {{ $title }}
        </h3>
    </div>
    <div class="divide-y divide-subtle-border border-t border-subtle-border dark:divide-primary-dark-border/5 dark:border-primary-dark-border/5">
        {{ $slot }}
    </div>
</section>