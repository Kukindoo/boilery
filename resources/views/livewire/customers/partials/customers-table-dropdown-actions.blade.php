@can('update', $customer)
    <flux:dropdown position="bottom" align="end">
        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" :aria-label="__('Akce pro :name', ['name' => $customer->name])" />
        <flux:menu>
            <flux:menu.item icon="pencil" :href="route('customers.edit', $customer)">
                Upravit
            </flux:menu.item>
        </flux:menu>
    </flux:dropdown>
@endcan
