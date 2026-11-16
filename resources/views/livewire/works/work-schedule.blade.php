<div>
    <x-stellaris.panel.base header="Plánovaný termín">
        <x-slot:action>
            @can('update', $form->work)
                <flux:button size="sm" wire:click="open">
                    {{ $form->work->schedule_at ? 'Upravit termín' : 'Nastavit termín' }}
                </flux:button>
            @endcan
        </x-slot:action>

        @if ($form->work->schedule_at)
            <x-stellaris.panel.table.col-3-base>
                <x-stellaris.container.show-row title="Datum">
                    {{ $form->work->schedule_at->copy()->timezone('Europe/Prague')->format('d-m-Y') }}
                </x-stellaris.container.show-row>
                <x-stellaris.container.show-row title="Čas (Europe/Prague)">
                    {{ $form->work->schedule_at->copy()->timezone('Europe/Prague')->format('H:i') }}
                </x-stellaris.container.show-row>
                <x-stellaris.container.show-row title="">
                    <flux:button size="sm" :href="$form->work->googleCalendarUrl()" target="_blank" rel="noopener noreferrer">
                        Přidat do Google Kalendáře
                    </flux:button>
                </x-stellaris.container.show-row>
            </x-stellaris.panel.table.col-3-base>
        @else
            <flux:text>Termín není naplánován.</flux:text>
        @endif
    </x-stellaris.panel.base>

    @can('update', $form->work)
        <flux:modal :name="'work-schedule-' . $form->work->id" class="md:w-96">
            <form wire:submit="submit" class="space-y-6">
                <flux:heading size="lg">Naplánovat zakázku</flux:heading>
                <flux:date-picker wire:model="form.scheduleDate" label="Datum" :min="$form->work->work_accepted_at?->format('Y-m-d')" clearable />
                <flux:error name="form.scheduleDate" />
                <flux:time-picker wire:model="form.scheduleTime" label="Čas (Europe/Prague)" time-format="24-hour" min="09:00" max="19:00" clearable />
                <flux:error name="form.scheduleTime" />
                <div class="flex justify-end gap-3">
                    <flux:modal.close>
                        <flux:button type="button">Zrušit</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                        Uložit termín
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    @endcan
</div>
