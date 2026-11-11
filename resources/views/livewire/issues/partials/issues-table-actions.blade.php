@php
    use App\Models\Issue;
@endphp

@can('create', Issue::class)
    <flux:button size="sm" icon="plus" :href="route('issues.create')">
        Nový případ
    </flux:button>
@endcan
