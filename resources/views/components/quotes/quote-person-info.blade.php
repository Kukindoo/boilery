@props(['quote'])

<div class="overflow-hidden bg-card-bg shadow-sm sm:rounded-lg dark:bg-card-dark-bg dark:shadow-none dark:inset-ring dark:inset-ring-primary-dark-border/10">
    <div class="px-4 py-6 sm:px-6 flex items-center justify-between">
        <h3 class="text-base/7 font-semibold text-heading-text dark:text-primary-dark-text">
            Informace o poptávce
        </h3>
        <div class="ml-4 shrink-0 print:hidden">
            <flux:modal.trigger name="edit-personal-info">
                <flux:button>Upravit</flux:button>
            </flux:modal.trigger>
        </div>
    </div>
    <div class="border-t border-subtle-border dark:border-primary-dark-border/5">
        <dl class="divide-y divide-subtle-border dark:divide-primary-dark-border/5">
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-heading-text dark:text-secondary-dark-text">
                    Zákazník
                </dt>
                <dd class="mt-1 text-sm/6 text-body-text sm:col-span-2 sm:mt-0 dark:text-muted-dark-text">
                    <x-customers.customer-name :customer="$quote->user" :name="trim($quote->first_name.' '.$quote->last_name)" />
                </dd>
                <dt class="text-sm font-medium text-heading-text dark:text-secondary-dark-text">
                    Telefón
                </dt>
                <dd class="mt-1 text-sm/6 text-body-text sm:col-span-2 sm:mt-0 dark:text-muted-dark-text">
                    {{ $quote->phone }}
                </dd>
                <dt class="text-sm font-medium text-heading-text dark:text-secondary-dark-text">
                    E-mail
                </dt>
                <dd class="mt-1 text-sm/6 text-body-text sm:col-span-2 sm:mt-0 dark:text-muted-dark-text">
                    {{ $quote->email }}
                </dd>
                <dt class="text-sm font-medium text-heading-text dark:text-secondary-dark-text">
                    Adresa
                </dt>
                <dd class="mt-1 text-sm/6 text-body-text sm:col-span-2 sm:mt-0 dark:text-muted-dark-text">
                    {{ $quote->address ?? 'Neznámá' }}
                </dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-heading-text dark:text-secondary-dark-text">
                    Zpráva
                </dt>
                <dd class="mt-1 text-sm/6 text-body-text sm:col-span-2 sm:mt-0 dark:text-muted-dark-text">
                    {{ $quote->message }}
                </dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-heading-text dark:text-secondary-dark-text">
                    Nahlášeno
                </dt>
                <dd class="mt-1 text-sm/6 text-body-text sm:col-span-2 sm:mt-0 dark:text-muted-dark-text">
                    {{ $quote->created_at }}
                </dd>
            </div>
        </dl>
    </div>
</div>

<flux:modal name="edit-personal-info" class="w-full max-w-5xl">
    <div class="space-y-6">
        <flux:heading size="lg">Upravit informace o poptávce</flux:heading>

        <livewire:quotes.quote-edit :quote="$quote"/>
    </div>
</flux:modal>
