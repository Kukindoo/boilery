<x-layouts::app :title="'Případ #' . $issue->id">
    <div class="space-y-6">
        <x-stellaris.container.show-card :title="'Případ #' . $issue->id">
            <x-slot name="action">
                <flux:button :href="route('issues.index')">
                    Zpět
                </flux:button>
                @can('update', $issue)
                    <flux:button :href="route('issues.edit', $issue)">
                        Upravit
                    </flux:button>
                @endcan
            </x-slot>
            <x-stellaris.container.show-row title="Status">
            <flux:badge :color="$issue->status->colour()">
                {{ $issue->status->label() }}
            </flux:badge>
        </x-stellaris.container.show-row>
        <x-stellaris.container.show-row title="Zákazník">
                <x-customers.customer-name :customer="$issue->user" />
            </x-stellaris.container.show-row>
            <x-stellaris.container.show-row title="E-mail">
                {{ $issue->user?->email ?: '—' }}
            </x-stellaris.container.show-row>
            <x-stellaris.container.show-row title="Telefon">
                {{ $issue->user?->phone ?: '—' }}
            </x-stellaris.container.show-row>
            <x-stellaris.container.show-row title="Adresa">
                {{ $issue->user?->address ?: '—' }}
            </x-stellaris.container.show-row>
            <x-stellaris.container.show-row title="Zahájeno">
                {{ $issue->started_at?->format('d-m-Y H:i') ?? '—' }}
            </x-stellaris.container.show-row>
            <x-stellaris.container.show-row title="Dokončeno">
                {{ $issue->finished_at?->format('d-m-Y H:i') ?? '—' }}
            </x-stellaris.container.show-row>
            <x-stellaris.container.show-row title="Vytvořeno">
                {{ $issue->created_at?->format('d-m-Y H:i') ?? '—' }}
            </x-stellaris.container.show-row>
            <x-stellaris.container.show-row title="Aktualizováno">
                {{ $issue->updated_at?->format('d-m-Y H:i') ?? '—' }}
            </x-stellaris.container.show-row>
        </x-stellaris.container.show-card>

        <livewire:customers.tables.quotes-table :issue="$issue" heading="Poptávky případu" :paginate="false" />

        <livewire:customers.tables.works-table :issue="$issue" heading="Zakázky případu" :paginate="false" />
    </div>
</x-layouts::app>
