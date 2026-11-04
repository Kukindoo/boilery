@can('create', \App\Models\Work::class)
    <flux:button size="sm" icon="plus" :href="route('works.create')">
        Přidat zakázku
    </flux:button>
@endcan