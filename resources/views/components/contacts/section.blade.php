<div class="relative isolate bg-white dark:bg-primary-dark-bg">
    <div class="mx-auto grid max-w-7xl grid-cols-1 lg:grid-cols-2">
        <div class="relative px-6 pt-24 pb-20 sm:pt-32 lg:static lg:px-8 lg:py-48">
            <div class="mx-auto max-w-xl lg:mx-0 lg:max-w-lg">
                <h2 class="text-4xl font-semibold tracking-tight text-pretty text-gray-900 sm:text-5xl dark:text-white">
                    Kontaktujte nás
                </h2>
                <p class="mt-6 text-lg/8 text-gray-600 dark:text-gray-400">
                    Pokud víte který bojler je třeba opravit, prosím, vyplňte formulář vpravo. Pro vice informací nás můžete zastihnout na telefonu nebo emailu dole.
                </p>
                <dl class="mt-10 space-y-4 text-base/7 text-gray-600 dark:text-gray-300">
                    <div class="flex gap-x-4">
                        <dt class="flex-none">
                            <span class="sr-only">Telephone</span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" data-slot="icon" aria-hidden="true" class="h-7 w-6 text-gray-400">
                                <path d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </dt>
                        <dd><a href="tel:{{ config('contacts.phone') }}" class="hover:text-gray-900 dark:hover:text-white">
                                {{ config('contacts.phone') }}
                            </a></dd>
                    </div>
                    <div class="flex gap-x-4">
                        <dt class="flex-none">
                            <span class="sr-only">Email</span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" data-slot="icon" aria-hidden="true" class="h-7 w-6 text-gray-400">
                                <path d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </dt>
                        <dd><a href="mailto:{{ config('contacts.email') }}" class="hover:text-gray-900 dark:hover:text-white">{{ config('contacts.email') }}</a></dd>
                    </div>
                </dl>
            </div>
        </div>
        @livewire('contact-form')
    </div>
</div>