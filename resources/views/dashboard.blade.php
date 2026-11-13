@php
    use App\Enums\Roles;
@endphp

<x-layouts::app :title="__('Přehled')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        @if(auth()->user()->hasExactRoles(Roles::CUSTOMER->label()))
            <livewire:quotes.customer-quotes-table/>
        @endif

        @if(auth()->user()->hasAnyRole(Roles::ADMIN->value, Roles::STAKEHOLDER->value))
            <div class="grid gap-4 md:grid-cols-3">
                <x-stellaris.statistics.base :statistic="$statistics['issues']" />
                <x-stellaris.statistics.base :statistic="$statistics['quotes']" />
                <x-stellaris.statistics.base :statistic="$statistics['works']" />
            </div>
        @endif
    </div>
</x-layouts::app>
