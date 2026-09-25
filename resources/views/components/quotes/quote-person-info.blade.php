@props(['quote'])

<div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800/50 dark:shadow-none dark:inset-ring dark:inset-ring-white/10">
    <div class="px-4 py-6 sm:px-6 flex items-center justify-between">
        <h3 class="text-base/7 font-semibold text-gray-900 dark:text-white">
            Informace o poptávce
        </h3>
        <div class="ml-4 shrink-0 print:hidden">
            <flux:modal.trigger name="edit-personal-info">
                <flux:button>Upravit</flux:button>
            </flux:modal.trigger>
        </div>
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

<flux:modal name="edit-personal-info" class="w-full max-w-5xl">
    <div class="space-y-6">
        <flux:heading size="lg">Upravit informace o poptávce</flux:heading>

        <livewire:quotes.quote-edit :quote="$quote"/>
    </div>
</flux:modal>