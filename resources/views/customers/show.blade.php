@php
    use App\Enums\Permissions;
@endphp

<x-layouts::app title="Zákazník">
    <div class="space-y-6">
        <x-stellaris.container.index-card :heading="$customer->name" :subheading="$customer->email">
            <x-slot name="actions">
                <div class="flex gap-2">
                    @can(Permissions::ISSUE_CREATE_ALL->label())
                        <flux:button size="sm" :href="route('issues.create', ['customer_id' => $customer->id])">
                            Přidat případ
                        </flux:button>
                    @endcan
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
        </x-stellaris.container.index-card>

        <livewire:customers.user-roles :user="$customer" />

        <livewire:issues.issues-table :customer="$customer" />
    </div>
</x-layouts::app>
