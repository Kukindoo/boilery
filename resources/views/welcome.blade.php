<x-layouts::guest :title="__('Welcome')">
    <div class="relative isolate px-6 pt-6 lg:px-8">
        <div class="mx-auto max-w-2xl py-16 sm:py-24 lg:py-28">
            <div class="text-center">
                <h1 class="text-5xl font-semibold tracking-tight text-balance text-gray-900 sm:text-7xl dark:text-primary-dark-text">
                    Opravy a instalace bojleru
                </h1>
                <p class="mt-8 text-lg font-medium text-pretty text-gray-500 sm:text-xl/8 dark:text-primary-dark-text">
                    Opravujeme a instalujeme bojlery značek Dražice, Ariston and Stiebl&nbsp;Eltron. Nové i záruky.
                </p>
                <div class="mt-10 flex items-center justify-center gap-x-6">
                    <a href="#" class="text-sm/6 font-semibold text-gray-900 dark:text-primary-dark-text">
                        Kontaktujte nás <span aria-hidden="true">→</span>
                    </a>
                </div>
            </div>
        </div>
    </div>


    <x-contacts.section />
</x-layouts::guest>