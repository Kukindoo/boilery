@props(['quote'])

@php
    use App\Enums\BoilerManufacturers;

    $manufacturer = $quote->boiler_manufacturer;

        if (is_null($manufacturer)) {
            $manufacturer = BoilerManufacturers::UNKNOWN;
        }
@endphp

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
                    {{ $manufacturer->label() }}
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
        </dl>
    </div>
</div>