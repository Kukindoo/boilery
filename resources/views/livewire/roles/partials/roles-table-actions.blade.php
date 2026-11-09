@php
    use Spatie\Permission\Models\Role;
@endphp

@can('create', Role::class)
    <flux:button size="sm" variant="primary" :href="route('roles.create')">
        Nová role
    </flux:button>
@endcan
