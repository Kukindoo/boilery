@php
    use App\Models\Issue;
@endphp
<flux:button size="sm" :href="route('customers.index')">
    Zpět na zákazníky
</flux:button>
<flux:dropdown position="bottom" align="end">
    <flux:button size="sm" icon:trailing="chevron-down">Akce</flux:button>
    <flux:menu>
        @can('create', Issue::class)
            <flux:menu.item icon="plus" :href="route('issues.create', ['customer_id' => $customer->id])">
                Přidat případ
            </flux:menu.item>
        @endcan
        @can('update', $customer)
            <flux:menu.item icon="pencil" :href="route('customers.edit', $customer)">
                Upravit
            </flux:menu.item>
        @endcan
    </flux:menu>
</flux:dropdown>
