@props(['quote'])

@php
    use App\Enums\BoilerManufacturers;

    $manufacturer = $quote->boiler_manufacturer;

        if (is_null($manufacturer)) {
            $manufacturer = BoilerManufacturers::UNKNOWN;
        }
@endphp

<div class="overflow-hidden bg-card-bg shadow-sm sm:rounded-lg dark:bg-card-dark-bg dark:shadow-none dark:inset-ring dark:inset-ring-primary-dark-border/10">
    <div class="px-4 py-6 sm:px-6">
        <h3 class="text-base/7 font-semibold text-heading-text dark:text-primary-dark-text">
            Informace o bojleru
        </h3>
    </div>
    <div class="border-t border-subtle-border dark:border-primary-dark-border/5">
        <dl class="divide-y divide-subtle-border dark:divide-primary-dark-border/5">
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-heading-text dark:text-secondary-dark-text">
                    Výrobce
                </dt>
                <dd class="mt-1 text-sm/6 text-body-text sm:col-span-2 sm:mt-0 dark:text-muted-dark-text">
                    {{ $manufacturer->label() }}
                </dd>

                <dt class="text-sm font-medium text-heading-text dark:text-secondary-dark-text">
                    Typ bojleru
                </dt>
                <dd class="mt-1 text-sm/6 text-body-text sm:col-span-2 sm:mt-0 dark:text-muted-dark-text">
                    {{ $quote->boiler_type ?? 'Neznámý'  }}
                </dd>
                <dt class="text-sm font-medium text-heading-text dark:text-secondary-dark-text">
                    Štítek
                </dt>
                <dd class="mt-1 text-sm/6 text-body-text sm:col-span-2 sm:mt-0 dark:text-muted-dark-text">
                    {{ $quote->boiler_serial_number ?? 'Neznámý' }}
                </dd>
                <dt class="text-sm font-medium text-heading-text dark:text-secondary-dark-text">
                    Adresa
                </dt>
                <dd class="mt-1 text-sm/6 text-body-text sm:col-span-2 sm:mt-0 dark:text-muted-dark-text">
                    {{ $quote->address ?? 'Neznámá' }}
                </dd>
            </div>
        </dl>
    </div>
</div>