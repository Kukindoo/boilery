@php
    use App\Enums\FileTypes;
@endphp

<x-layouts::app :title="__('Zakázky')">
    <x-stellaris.panel.container.base>
        <x-slot:header>
            <x-stellaris.panel.header.base :heading="__('Zakázka #:work_id', ['work_id' => $work->id])">
                <x-slot:subheading>
                    <x-customers.customer-name :customer="$work->user" />
                </x-slot>
            </x-stellaris.panel.header.base>
        </x-slot:header>
        <x-slot:actionsStrip>
            <x-stellaris.panel.actions-strip.base>
                <x-slot:slotLeft>
                    <flux:button :href="route('works.index')" icon="arrow-left" size="sm">
                        {{ __('Zpět na Zakázky') }}
                    </flux:button>
                </x-slot:slotLeft>
                @include('works.partials.show-actions')
            </x-stellaris.panel.actions-strip.base>
        </x-slot:actionsStrip>


            <x-works.work-info :work="$work"/>
            <x-works.work-boiler-info :work="$work"/>
            <x-works.work-repair-info :work="$work"/>
            <livewire:spare-parts.work-spare-parts :work="$work"/>
            <livewire:assembly-actions.work-assembly-actions :work="$work" />
            <x-works.work-transport-info :work="$work"/>
            <x-files.attachment-card :model="$work"/>

            @can('viewAny', \Spatie\Activitylog\Models\Activity::class)
                <livewire:activity-logs.activity-logs-table
                    :subject-type="\App\Models\Work::class"
                    :subject-id="$work->id"
                />
            @endcan

            <x-pdfs.pdf-modal-preview
                :model="$work"
                :file-type="FileTypes::SERVICE_PROTOCOL->value"
                :show-trigger="false"
            />
    </x-stellaris.panel.container.base>
</x-layouts::app>
