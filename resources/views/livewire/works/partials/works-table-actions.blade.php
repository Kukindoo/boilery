@can('create', \App\Models\Work::class)
    <flux:button size="sm" :href="route('works.create')">
        Přidat zakázku
    </flux:button>
@endcan