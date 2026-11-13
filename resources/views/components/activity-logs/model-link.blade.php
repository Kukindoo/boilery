@props(['model' => null, 'label' => null])

@php
    $routeName = match (true) {
        $model instanceof \App\Models\User => 'customers.show',
        $model instanceof \App\Models\Work => 'works.show',
        $model instanceof \App\Models\Quote => 'quotes.show',
        $model instanceof \App\Models\Issue => 'issues.show',
        $model instanceof \App\Models\AssemblyAction => 'assembly-actions.show',
        $model instanceof \App\Models\SparePart => 'spare-parts.show',
        $model instanceof \Spatie\Permission\Models\Role => 'roles.show',
        $model instanceof \Spatie\Permission\Models\Permission => 'permissions.show',
        default => null,
    };
    $isDeleted = $model && method_exists($model, 'trashed') && $model->trashed();
@endphp

@if ($routeName && ! $isDeleted && auth()->user()?->can('view', $model))
    <flux:link :href="route($routeName, $model)">{{ $label ?? $model->getKey() }}</flux:link>
@else
    {{ $label ?? $model?->getKey() ?? '—' }}
@endif
