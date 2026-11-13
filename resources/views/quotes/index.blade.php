@php
    use Spatie\Activitylog\Models\Activity;
@endphp

<x-layouts::app title="Poptávky">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <livewire:quotes.quotes-table/>
        @can('viewAny', Activity::class)
            <livewire:activity-logs.activity-logs-table
                    :subject-type="\App\Models\Quote::class"
            />
        @endcan
    </div>
</x-layouts::app>