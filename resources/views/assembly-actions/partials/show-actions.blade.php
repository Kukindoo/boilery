<flux:dropdown position="bottom" align="end">
    <flux:button size="sm" icon:trailing="chevron-down">Akce</flux:button>
    <flux:menu>
        <flux:menu.item :href="route('assembly-actions.index')">
            Zpět na seznam
        </flux:menu.item>
        @can('update', $assemblyAction)
            <flux:menu.item :href="route('assembly-actions.edit', $assemblyAction)">
                Upravit
            </flux:menu.item>
        @endcan
    </flux:menu>
</flux:dropdown>
