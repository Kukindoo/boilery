<x-stellaris.livewire.table.wrapper heading="Aktivity">
    <x-slot name="filters">
        <div class="flex flex-col gap-4">
            <flux:input wire:model.live.debounce.300ms="search" type="search" label="Hledat aktivity"
                placeholder="Popis, uživatel, model, ID nebo další údaje…" clearable />
            <div class="grid grid-cols-1 items-end gap-4 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_auto]">
                <flux:select wire:model.live="selectedUser" variant="listbox" searchable clearable label="Uživatel" placeholder="Všichni uživatelé">
                    @foreach ($this->filterUsers as $user)
                        <flux:select.option :value="(string) $user->id" :keywords="$user->email">{{ $user->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model.live="selectedModel" variant="listbox" searchable clearable label="Model" placeholder="Všechny modely">
                    @foreach ($this->filterModels as $model)
                        <flux:select.option :value="$model" :keywords="$model">{{ class_basename($model) }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model.live="selectedEvent" variant="listbox" searchable clearable label="Událost" placeholder="Všechny události">
                    @foreach ($this->filterEvents as $event)
                        <flux:select.option :value="$event">{{ $event }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:button wire:click="clearFilters">Vymazat filtry</flux:button>
            </div>
        </div>
    </x-slot>
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
                            <x-customers.customer-name :customer="$activity->causer"/>
                        @else
                            {{ $activity->causer?->getAttribute('name') ?? 'Systém / neznámý' }}
                        @endif</flux:table.cell>
                    <flux:table.cell>{{ $activity->subject_type ? class_basename($activity->subject_type) : '—' }}</flux:table.cell>
                    <flux:table.cell>
                        <x-activity-logs.model-link :model="$activity->subject" :label="$activity->subject_id ?? '—'"/>
                    </flux:table.cell>
                    <flux:table.cell>{{ $activity->event ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="min-w-56 max-w-80 whitespace-normal">
                        <flux:modal.trigger :name="'activity-detail-' . $this->getId() . '-' . $activity->id">
                            <button type="button"
                                    class="line-clamp-2 cursor-pointer rounded text-left text-sm leading-6 capitalize wrap-anywhere underline decoration-zinc-300 underline-offset-4 hover:decoration-current focus-visible:outline-2 focus-visible:outline-offset-4 dark:decoration-zinc-600">
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
        <flux:modal :name="'activity-detail-' . $this->getId() . '-' . $activity->id"
                    class="w-[calc(100vw-2rem)] max-w-none">
            <div class="flex flex-col gap-6">
                <div class="flex flex-col gap-2 pr-8">
                    <flux:heading size="lg">Detail aktivity</flux:heading>
                    <flux:text>Dne {{ $activity->created_at?->format('d. m. Y H:i:s') }}
                        aktorem @if ($activity->causer instanceof \App\Models\User)
                            <x-customers.customer-name :customer="$activity->causer"/>
                        @else
                            {{ $activity->causer?->getAttribute('name') ?? 'Systém / neznámý' }}
                        @endif</flux:text>
                    <flux:text>
                        <x-activity-logs.model-link :model="$activity->subject"
                                                    :label="($activity->subject_type ? class_basename($activity->subject_type) : '—') . ' #' . ($activity->subject_id ?? '—')"/>
                        · {{ $activity->event ?? '—' }}
                    </flux:text>
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
                                <x-customers.customer-name :customer="$activity->causer"/>
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
                                @if ($label === 'ID záznamu' || $label === 'Model')
                                    <x-activity-logs.model-link :model="$activity->subject" :label="$value ?? '—'"/>
                                @elseif ($label === 'ID původce')
                                    <x-activity-logs.model-link :model="$activity->causer" :label="$value ?? '—'"/>
                                @else
                                    {{ $value ?? '—' }}
                                @endif
                            </x-stellaris.container.show-row>
                        @endforeach
                    </x-stellaris.panel.table.col-3-base>
                </x-stellaris.panel.base>

                <x-stellaris.panel.base header="Změny">
                    @php
                        $old = $activity->attribute_changes?->get('old', []) ?? [];
                        $new = $activity->attribute_changes?->get('attributes', []) ?? [];
                        $fields = array_unique(array_merge(array_keys($old), array_keys($new)));
                    @endphp
                    <flux:table class="table-fixed">
                        <flux:table.columns>
                            <flux:table.column class="w-1/4">Pole</flux:table.column>
                            <flux:table.column>Před</flux:table.column>
                            <flux:table.column>Po</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @forelse ($fields as $field)
                                <flux:table.row>
                                    <flux:table.cell
                                            class="py-2 align-top whitespace-normal font-medium wrap-anywhere">{{ $field }}</flux:table.cell>
                                    @foreach ([$old, $new] as $values)
                                        <flux:table.cell class="py-2 align-top whitespace-normal wrap-anywhere">
                                            @if (! array_key_exists($field, $values))
                                                <span class="text-zinc-400 dark:text-zinc-500">—</span>
                                            @elseif ($values[$field] === null)
                                                <span class="text-zinc-400 dark:text-zinc-500">null</span>
                                            @elseif (is_bool($values[$field]))
                                                {{ $values[$field] ? 'Ano' : 'Ne' }}
                                            @elseif (is_array($values[$field]) || is_object($values[$field]))
                                                <pre class="whitespace-pre-wrap text-xs leading-5 wrap-anywhere">{{ json_encode($values[$field], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                            @else
                                                <span class="whitespace-pre-wrap">{{ $values[$field] === '' ? 'Prázdný text' : $values[$field] }}</span>
                                            @endif
                                        </flux:table.cell>
                                    @endforeach
                                </flux:table.row>
                            @empty
                                <flux:table.row>
                                    <flux:table.cell colspan="3" class="py-2">Žádné zaznamenané změny polí.
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforelse
                        </flux:table.rows>
                    </flux:table>
                </x-stellaris.panel.base>

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
