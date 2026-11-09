@php
    use App\Models\Work;
@endphp

@can('create', Work::class)
    <flux:button size="sm" icon="plus" :href="route('works.create')">
        Přidat zakázku
    </flux:button>
@endcan