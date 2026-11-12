<x-layouts::app title="Zákazník">
    <div class="space-y-6">
        <x-stellaris.container.index-card :heading="$customer->name" :subheading="$customer->email">
            <x-slot name="actions">
                @include('customers.partials.show-actions')
            </x-slot>

            <x-customers.customer-info :customer="$customer" />
        </x-stellaris.container.index-card>

        <livewire:customers.user-roles :user="$customer" />

        <livewire:issues.issues-table :customer="$customer" />
    </div>
</x-layouts::app>
