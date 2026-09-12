<x-layouts::guest :title="config('app.name'). ' - ' . __('Welcome')">
    <div class="relative isolate px-6 pt-6 lg:px-8">
        <div class="mx-auto max-w-2xl py-16 sm:py-24 lg:py-28">
            <div class="text-center">
                <h1 class="text-5xl font-semibold tracking-tight text-balance text-gray-900 sm:text-7xl dark:text-primary-dark-text">
                    Opravy a instalace bojleru
                </h1>

                <div class="mx-auto max-w-7xl px-6 lg:px-8 mt-6">
                    <h2 class="text-center text-lg/8 font-semibold text-gray-900 dark:text-primary-dark-text">Věří nám světové nejinovativnější značky</h2>
                    <div class="mx-auto mt-10 grid max-w-lg grid-cols-6 items-center gap-x-8 gap-y-10 sm:max-w-xl sm:grid-cols-6 sm:gap-x-10 lg:mx-0 lg:max-w-none lg:grid-cols-3">
                        <img width="158" height="48" src="{{ asset('images/drazice-logo.jpg') }}" alt="Drazice logo" class="col-span-2 max-h-12 w-full object-contain lg:col-span-1 dark:hidden" />
                        <img width="158" height="48" src="{{ asset('images/drazice-logo.jpg') }}" alt="Drazice logo" class="col-span-2 max-h-12 w-full object-contain not-dark:hidden lg:col-span-1" />

                        <img width="158" height="48" src="{{ asset('images/ariston-logo.svg') }}" alt="Ariston logo" class="col-span-2 max-h-12 w-full object-contain lg:col-span-1 dark:hidden" />
                        <img width="158" height="48" src="{{ asset('images/ariston-logo.svg') }}" alt="Ariston logo" class="col-span-2 max-h-12 w-full object-contain not-dark:hidden lg:col-span-1" />

                        <img width="158" height="48" src="{{ asset('images/stiebl-eltron-logo.jpg') }}" alt="Stiebl Eltron" class="col-span-2 max-h-12 w-full object-contain lg:col-span-1 dark:hidden" />
                        <img width="158" height="48" src="{{ asset('images/stiebl-eltron-logo.jpg') }}" alt="Stiebl Eltron" class="col-span-2 max-h-12 w-full object-contain not-dark:hidden lg:col-span-1" />
                    </div>
                </div>

                <p class="mt-8 text-lg font-medium text-pretty text-gray-500 sm:text-xl/8 dark:text-primary-dark-text">
                    Opravujeme a instalujeme bojlery značek Dražice, Ariston and Stiebl&nbsp;Eltron. Nové i záruky.
                </p>
                <div class="mt-10 flex items-center justify-center gap-x-6">
                    <a href="#contact-quote-form" class="text-sm/6 font-semibold text-gray-900 dark:text-primary-dark-text">
                        Kontaktujte nás <span aria-hidden="true">→</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <x-contacts.section />
</x-layouts::guest>