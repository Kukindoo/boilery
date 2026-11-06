<x-layouts::app title="Upravit zákazníka">
    <div class="space-y-6">
        <x-stellaris.container.index-card heading="Upravit zákazníka" :subheading="$customer->name">
            <x-slot name="actions">
                <flux:button size="sm" :href="route('customers.show', $customer)">
                    Zpět na zákazníka
                </flux:button>
            </x-slot>

            <livewire:customers.customer-edit :customer="$customer" />
        </x-stellaris.container.index-card>
    </div>
</x-layouts::app>
