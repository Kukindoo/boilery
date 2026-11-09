<flux:button :href="route('assembly-actions.index')">
    Zpět na seznam
</flux:button>
@can('update', $assemblyAction)
    <flux:button :href="route('assembly-actions.edit', $assemblyAction)">
        Upravit
    </flux:button>
@endcan
