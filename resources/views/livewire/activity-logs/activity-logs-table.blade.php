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
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->activities as $activity)
                <flux:table.row :key="$activity->id">
                    <flux:table.cell>{{ $activity->created_at?->format('d. m. Y H:i:s') ?? '—' }}</flux:table.cell>
                    <flux:table.cell>@if ($activity->causer instanceof \App\Models\User)
                            <x-customers.customer-name :customer="$activity->causer" />
                        @else
                            {{ $activity->causer?->getAttribute('name') ?? 'Systém / neznámý' }}
                        @endif</flux:table.cell>
                    <flux:table.cell>{{ $activity->subject_type ? class_basename($activity->subject_type) : '—' }}</flux:table.cell>
                    <flux:table.cell>{{ $activity->subject_id ?? '—' }}</flux:table.cell>
                    <flux:table.cell>{{ $activity->event ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="min-w-56 max-w-80 whitespace-normal">
                        <flux:modal.trigger :name="'activity-detail-' . $this->getId() . '-' . $activity->id">
                            <button type="button" class="line-clamp-2 cursor-pointer rounded text-left text-sm leading-6 capitalize wrap-anywhere underline decoration-zinc-300 underline-offset-4 hover:decoration-current focus-visible:outline-2 focus-visible:outline-offset-4 dark:decoration-zinc-600">
                                {{ $activity->description }}
                            </button>
                        </flux:modal.trigger>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6">Žádné aktivity.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
    @foreach ($this->activities as $activity)
        <flux:modal :name="'activity-detail-' . $this->getId() . '-' . $activity->id" class="w-full max-w-xl">
            <div class="flex flex-col gap-6">
                <div class="flex flex-col gap-2 pr-8">
                    <flux:heading size="lg">Detail aktivity</flux:heading>
                    <flux:text>Dne {{ $activity->created_at?->format('d. m. Y H:i:s') }} aktorem @if ($activity->causer instanceof \App\Models\User)
                            <x-customers.customer-name :customer="$activity->causer" />
                        @else
                            {{ $activity->causer?->getAttribute('name') ?? 'Systém / neznámý' }}
                        @endif</flux:text>
                    <flux:text>{{ $activity->subject_type ? class_basename($activity->subject_type) : '—' }} #{{ $activity->subject_id ?? '—' }} · {{ $activity->event ?? '—' }}</flux:text>
                </div>

                <x-stellaris.panel.base header="Popis">
                    <x-stellaris.panel.table.col-3-base>
                        <x-stellaris.container.show-row title="Popis">
                            <span class="whitespace-pre-wrap capitalize">{{ $activity->description }}</span>
                        </x-stellaris.container.show-row>
                    </x-stellaris.panel.table.col-3-base>
                </x-stellaris.panel.base>

                <x-stellaris.panel.base header="Informace o aktivitě">
                    <x-stellaris.panel.table.col-3-base>
                        <x-stellaris.container.show-row title="Uživatel">
                            @if ($activity->causer instanceof \App\Models\User)
                            <x-customers.customer-name :customer="$activity->causer" />
                        @else
                            {{ $activity->causer?->getAttribute('name') ?? 'Systém / neznámý' }}
                        @endif
                        </x-stellaris.container.show-row>
                        @foreach ([
                            'ID aktivity' => $activity->id,
                            'Název logu' => $activity->log_name,
                            'Událost' => $activity->event,
                            'Model' => $activity->subject_type,
                            'ID záznamu' => $activity->subject_id,
                            'Typ původce' => $activity->causer_type,
                            'ID původce' => $activity->causer_id,
                            'Vytvořeno' => $activity->created_at?->format('d. m. Y H:i:s'),
                            'Aktualizováno' => $activity->updated_at?->format('d. m. Y H:i:s'),
                        ] as $label => $value)
                            <x-stellaris.container.show-row :title="$label">
                                {{ $value ?? '—' }}
                            </x-stellaris.container.show-row>
                        @endforeach
                    </x-stellaris.panel.table.col-3-base>
                </x-stellaris.panel.base>

                <div class="flex flex-col gap-4">
                    <flux:heading>Změny</flux:heading>
                    @php
                        $old = $activity->attribute_changes?->get('old', []) ?? [];
                        $new = $activity->attribute_changes?->get('attributes', []) ?? [];
                        $fields = array_unique(array_merge(array_keys($old), array_keys($new)));
                    @endphp
                    @forelse ($fields as $field)
                        <x-stellaris.panel.base :header="$field">
                            <x-stellaris.panel.table.col-3-base>
                                @foreach (['Před' => $old, 'Po' => $new] as $label => $values)
                                    <x-stellaris.container.show-row :title="$label">
                                        @if (! array_key_exists($field, $values))
                                            —
                                        @elseif ($values[$field] === null)
                                            null
                                        @elseif (is_bool($values[$field]))
                                            {{ $values[$field] ? 'Ano' : 'Ne' }}
                                        @elseif (is_array($values[$field]) || is_object($values[$field]))
                                            <pre class="whitespace-pre-wrap text-sm leading-6 wrap-anywhere">{{ json_encode($values[$field], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                        @else
                                            <span class="whitespace-pre-wrap">{{ $values[$field] === '' ? 'Prázdný text' : $values[$field] }}</span>
                                        @endif
                                    </x-stellaris.container.show-row>
                                @endforeach
                            </x-stellaris.panel.table.col-3-base>
                        </x-stellaris.panel.base>
                    @empty
                        <x-stellaris.panel.base header="Změny polí">
                            <x-stellaris.panel.table.col-3-base>
                                <x-stellaris.container.show-row title="Změny">
                                    Žádné zaznamenané změny polí.
                                </x-stellaris.container.show-row>
                            </x-stellaris.panel.table.col-3-base>
                        </x-stellaris.panel.base>
                    @endforelse
                </div>

                <x-stellaris.panel.base header="Další vlastnosti">
                    <x-stellaris.panel.table.col-3-base>
                        @forelse ($activity->properties ?? [] as $property => $value)
                            <x-stellaris.container.show-row :title="$property">
                                <pre class="whitespace-pre-wrap text-sm leading-6 wrap-anywhere">{{ is_string($value) ? $value : json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                            </x-stellaris.container.show-row>
                        @empty
                            <x-stellaris.container.show-row title="Vlastnosti">
                                Žádné další vlastnosti.
                            </x-stellaris.container.show-row>
                        @endforelse
                    </x-stellaris.panel.table.col-3-base>
                </x-stellaris.panel.base>

                <div class="flex justify-end">
                    <flux:modal.close>
                        <flux:button>Zavřít</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        </flux:modal>
    @endforeach
</x-stellaris.livewire.table.wrapper>
