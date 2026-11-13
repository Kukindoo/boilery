@php
//  {{-- All logs --}}
//  <livewire:activity-logs.activity-logs-table />
//
//  {{-- Logs for a model type --}}
//  <livewire:activity-logs.activity-logs-table
//          :subject-type="\App\Models\Work::class"
//  />
//
//  {{-- Logs for one record --}}
//  <livewire:activity-logs.activity-logs-table
//          :subject-type="\App\Models\Work::class"
//          :subject-id="$work->id"
//  />
@endphp
<x-stellaris.livewire.table.wrapper heading="Aktivity">
    <flux:table :paginate="$this->activities">
        <flux:table.columns>
            <flux:table.column>Datum</flux:table.column>
            <flux:table.column>Uživatel</flux:table.column>
            <flux:table.column>Model</flux:table.column>
            <flux:table.column>ID záznamu</flux:table.column>
            <flux:table.column>Událost</flux:table.column>
            <flux:table.column>Popis</flux:table.column>
            <flux:table.column>Změny</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->activities as $activity)
                <flux:table.row :key="$activity->id">
                    <flux:table.cell>{{ $activity->created_at?->format('d. m. Y H:i:s') ?? '—' }}</flux:table.cell>
                    <flux:table.cell>{{ $activity->causer?->getAttribute('name') ?? 'Systém / neznámý' }}</flux:table.cell>
                    <flux:table.cell>{{ $activity->subject_type ? class_basename($activity->subject_type) : '—' }}</flux:table.cell>
                    <flux:table.cell>{{ $activity->subject_id ?? '—' }}</flux:table.cell>
                    <flux:table.cell>{{ $activity->event ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="whitespace-normal wrap-anywhere">{{ $activity->description }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($activity->attribute_changes?->isNotEmpty())
                            <details>
                                <summary class="cursor-pointer">Zobrazit změny</summary>
                                <pre class="max-w-lg whitespace-pre-wrap wrap-anywhere">{{ json_encode($activity->attribute_changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                            </details>
                        @else
                            —
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7">Žádné aktivity.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</x-stellaris.livewire.table.wrapper>
