<flux:button :href="route('spare-parts.index')">
    Zpět na seznam
</flux:button>
@can('update', $sparePart)
    <flux:button :href="route('spare-parts.edit', $sparePart)">
        Upravit
    </flux:button>
@endcan
