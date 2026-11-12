<flux:dropdown position="bottom" align="end">
    <flux:button size="sm" icon:trailing="chevron-down">Akce</flux:button>
    <flux:menu>
        @can('update', $sparePart)
            <flux:menu.item icon="pencil" :href="route('spare-parts.edit', $sparePart)">
                Upravit
            </flux:menu.item>
        @endcan
    </flux:menu>
</flux:dropdown>
