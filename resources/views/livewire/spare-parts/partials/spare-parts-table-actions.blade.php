@php
    use App\Models\SparePart;
@endphp

@can('create', SparePart::class)
    <flux:button size="sm" icon="plus" :href="route('spare-parts.create')">
        Nový náhradní díl
    </flux:button>
@endcan
