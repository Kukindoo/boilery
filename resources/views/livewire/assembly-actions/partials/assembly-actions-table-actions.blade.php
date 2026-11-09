@php
    use App\Models\AssemblyAction;
@endphp

@can('create', AssemblyAction::class)
    <flux:button size="sm" icon="plus" :href="route('assembly-actions.create')">
        Nový pracovní úkon
    </flux:button>
@endcan
