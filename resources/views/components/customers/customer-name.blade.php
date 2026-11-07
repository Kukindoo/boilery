@props(['customer' => null, 'name' => null])

@if ($customer && auth()->user()?->can('view', $customer))
    <flux:link :href="route('customers.show', $customer)">{{ $name ?? $customer->name }}</flux:link>
@else
    {{ $name ?? $customer?->name ?? '—' }}
@endif
