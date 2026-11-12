<flux:dropdown position="bottom" align="end">
    <flux:button size="sm" icon:trailing="chevron-down">Akce</flux:button>
    <flux:menu>
        @can('update', $assemblyAction)
            <flux:menu.item icon="pencil" :href="route('assembly-actions.edit', $assemblyAction)">
                Upravit
            </flux:menu.item>
        @endcan
    </flux:menu>
</flux:dropdown>
