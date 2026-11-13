<x-layouts::app :title="'Případ #' . $issue->id">
    <x-stellaris.panel.container.base>
        <x-slot:header>
            <x-stellaris.panel.header.base :heading="__('Případ #:issue_id', ['issue_id' => $issue->id])">
                <x-slot:subheading>
                    <x-customers.customer-name :customer="$issue->user" />
                </x-slot>
            </x-stellaris.panel.header.base>
        </x-slot:header>
        <x-slot:actionsStrip>
            <x-stellaris.panel.actions-strip.base>
                <x-slot:slotLeft>
                    <flux:button :href="route('issues.index')" icon="arrow-left" size="sm">
                        {{ __('Zpět na Případy') }}
                    </flux:button>
                </x-slot:slotLeft>
                @include('issues.partials.show-actions')
            </x-stellaris.panel.actions-strip.base>
        </x-slot:actionsStrip>

        <x-issues.issue-summary :issue="$issue" />
        <x-issues.issue-person-info :issue="$issue" />
        <livewire:customers.tables.quotes-table :issue="$issue" heading="Poptávky případu" />
        <livewire:customers.tables.works-table :issue="$issue" heading="Zakázky případu" />
        @can('viewAny', \Spatie\Activitylog\Models\Activity::class)
            <livewire:activity-logs.activity-logs-table
                :subject-type="\App\Models\Issue::class"
                :subject-id="$issue->id"
            />
        @endcan
    </x-stellaris.panel.container.base>
</x-layouts::app>
