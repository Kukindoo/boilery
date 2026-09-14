<x-layouts::app :title="__('Poptávky')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <flux:heading>
            Poptávka {{ $quote->id }}
            <flux:text>{{ $quote->first_name }} {{ $quote->last_name }}</flux:text>
        </flux:heading>
        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800/50 dark:shadow-none dark:inset-ring dark:inset-ring-white/10">
            <div class="px-4 py-6 sm:px-6">
                <h3 class="text-base/7 font-semibold text-gray-900 dark:text-white">
                    Informace o poptávce
                </h3>
            </div>
            <div class="border-t border-gray-100 dark:border-white/5">
                <dl class="divide-y divide-gray-100 dark:divide-white/5">
                    <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            Zákazník
                        </dt>
                        <dd class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-300">
                            {{ $quote->first_name }} {{ $quote->last_name }}
                        </dd>
                        <dt class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            Telefón
                        </dt>
                        <dd class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-300">
                            {{ $quote->phone }}
                        </dd>
                        <dt class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            E-mail
                        </dt>
                        <dd class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-300">
                            {{ $quote->email }}
                        </dd>
                        <dt class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            Adresa
                        </dt>
                        <dd class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-300">
                            {{ $quote->address ?? 'Neznámá' }}
                        </dd>
                    </div>
                    <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            Zpráva
                        </dt>
                        <dd class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-300">
                            {{ $quote->message }}
                        </dd>
                    </div>
                    <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            Nahlášeno
                        </dt>
                        <dd class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-300">
                            {{ $quote->created_at }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800/50 dark:shadow-none dark:inset-ring dark:inset-ring-white/10">
            <div class="px-4 py-6 sm:px-6">
                <h3 class="text-base/7 font-semibold text-gray-900 dark:text-white">
                    Informace o bojleru
                </h3>
            </div>
            <div class="border-t border-gray-100 dark:border-white/5">
                <dl class="divide-y divide-gray-100 dark:divide-white/5">
                    <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            Výrobce
                        </dt>
                        <dd class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-300">
                            {{ \App\Enums\BoilerManufacturers::tryFrom($quote->boiler_manufacturer)->label() }}
                        </dd>

                        <dt class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            Typ bojleru
                        </dt>
                        <dd class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-300">
                            {{ $quote->boiler_type ?? 'Neznámý'  }}
                        </dd>
                        <dt class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            Štítek
                        </dt>
                        <dd class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-300">
                            {{ $quote->boiler_serial_number ?? 'Neznámý' }}
                        </dd>
                        <dt class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            Adresa
                        </dt>
                        <dd class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-300">
                            {{ $quote->address ?? 'Neznámá' }}
                        </dd>
                    </div>
                    <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm/6 font-medium text-gray-900 dark:text-gray-100">
                            Přílohy
                        </dt>
                        <dd class="mt-2 text-sm text-gray-900 sm:col-span-2 sm:mt-0 dark:text-gray-100">
                            <ul role="list"
                                class="divide-y divide-gray-100 rounded-md border border-gray-200 dark:divide-white/5 dark:border-white/10">
                                <livewire:quotes.quote-download-card
                                        label="Dokument štítku"
                                        :quote="$quote"
                                />

                                <livewire:quotes.quote-download-card
                                        label="Dokument kupní smlouvy"
                                        :quote="$quote"
                                />

                                <livewire:quotes.quote-download-card
                                        label="okument záručního listu"
                                        :quote="$quote"
                                />
                            </ul>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</x-layouts::app>