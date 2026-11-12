<flux:dropdown position="bottom" align="end">
    <flux:button size="sm" icon:trailing="chevron-down">Akce</flux:button>
    <flux:menu>
        @can('update', $issue)
            <flux:menu.item icon="pencil" :href="route('issues.edit', $issue)">
                Upravit
            </flux:menu.item>
        @endcan
    </flux:menu>
</flux:dropdown>
