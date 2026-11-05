<x-layouts::app title="Zákazník">
    <div class="space-y-6">
        <x-stellaris.container.model-card :heading="$customer->name" :subheading="$customer->email">
            <x-slot name="actions">
                <div class="flex gap-2">
                    <flux:button size="sm" :href="route('customers.index')">
                        Zpět na zákazníky
                    </flux:button>
                    @can('update', $customer)
                        <flux:button size="sm" icon="pencil" :href="route('customers.edit', $customer)">
                            Upravit
                        </flux:button>
                    @endcan
                </div>
            </x-slot>

            <x-customers.customer-info :customer="$customer" />
        </x-stellaris.container.model-card>
    </div>
</x-layouts::app>
