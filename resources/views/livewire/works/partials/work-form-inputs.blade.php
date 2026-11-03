<div class="space-y-2">
    <x-stellaris.livewire.forms.side-to-side.section title="Informace o zakázce">
        @if (auth()->user()->can($form->work ? \App\Enums\Permissions::WORK_EDIT_ALL->label() : \App\Enums\Permissions::WORK_CREATE_ALL->label()))
            <x-stellaris.livewire.forms.side-to-side.field label="ID Zákazníka">
                <flux:input type="number" wire:model="form.userId" disabled/>
            </x-stellaris.livewire.forms.side-to-side.field>
        @endif

        <x-stellaris.livewire.forms.side-to-side.field label="ID poptávky">
            <flux:input type="number" wire:model="form.quoteId" disabled/>
        </x-stellaris.livewire.forms.side-to-side.field>

        <x-stellaris.livewire.forms.side-to-side.field label="Číslo zakázky">
            <flux:input wire:model="form.orderId"/>
        </x-stellaris.livewire.forms.side-to-side.field>

        @if ($form->work)
            <x-stellaris.livewire.forms.side-to-side.field label="Status">
                <flux:select wire:model="form.status" variant="listbox">
                    @foreach (\App\Enums\WorkStatus::cases() as $status)
                        <flux:select.option :value="$status->value">
                            {{ $status->label() }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </x-stellaris.livewire.forms.side-to-side.field>
        @endif

        <x-stellaris.livewire.forms.side-to-side.field label="Datum přijetí">
            <flux:date-picker wire:model="form.workAcceptedAt"/>
        </x-stellaris.livewire.forms.side-to-side.field>

        <x-stellaris.livewire.forms.side-to-side.field label="Datum zahájení">
            <flux:date-picker wire:model="form.workStartedAt"/>
        </x-stellaris.livewire.forms.side-to-side.field>

        <x-stellaris.livewire.forms.side-to-side.field label="Datum dokončení">
            <flux:date-picker wire:model="form.workCompletedAt"/>
        </x-stellaris.livewire.forms.side-to-side.field>

        <x-stellaris.livewire.forms.side-to-side.field label="Jméno technika">
            <flux:input wire:model="form.mechanicName"/>
        </x-stellaris.livewire.forms.side-to-side.field>
    </x-stellaris.livewire.forms.side-to-side.section>

    <x-stellaris.livewire.forms.side-to-side.section title="Informace o výrobku">
        <x-stellaris.livewire.forms.side-to-side.field label="Typ výrobku">
            <flux:input wire:model="form.typeOfProduct"/>
        </x-stellaris.livewire.forms.side-to-side.field>

        <x-stellaris.livewire.forms.side-to-side.field label="Typové číslo">
            <flux:input wire:model="form.typeNumber"/>
        </x-stellaris.livewire.forms.side-to-side.field>

        <x-stellaris.livewire.forms.side-to-side.field label="Výrobní číslo">
            <flux:input wire:model="form.serialNumber"/>
        </x-stellaris.livewire.forms.side-to-side.field>

        <x-stellaris.livewire.forms.side-to-side.field label="Datum nákupu">
            <flux:date-picker type="date" wire:model="form.dayOfPurchase"/>
        </x-stellaris.livewire.forms.side-to-side.field>

        <x-stellaris.livewire.forms.side-to-side.field label="Typ budovy">
            <flux:input wire:model="form.buildingType"/>
        </x-stellaris.livewire.forms.side-to-side.field>

        <x-stellaris.livewire.forms.side-to-side.field label="Oprava v záruce">
            <flux:input wire:model="form.repairInWarranty"/>
        </x-stellaris.livewire.forms.side-to-side.field>

        <x-stellaris.livewire.forms.side-to-side.field label="Záruka uznána">
            <flux:checkbox wire:model="form.warrantyAccepted"/>
        </x-stellaris.livewire.forms.side-to-side.field>
    </x-stellaris.livewire.forms.side-to-side.section>

    <x-stellaris.livewire.forms.side-to-side.section title="Informace o opravě">
        <x-stellaris.livewire.forms.side-to-side.field label="Nahlášený problém">
            <flux:input wire:model="form.reportedProblem"/>
        </x-stellaris.livewire.forms.side-to-side.field>

        <x-stellaris.livewire.forms.side-to-side.field label="Provedené práce">
            <flux:textarea wire:model="form.workPerformedDescription"/>
        </x-stellaris.livewire.forms.side-to-side.field>

        <x-stellaris.livewire.forms.side-to-side.field label="Poznámka">
            <flux:textarea wire:model="form.notes"/>
        </x-stellaris.livewire.forms.side-to-side.field>
    </x-stellaris.livewire.forms.side-to-side.section>

    <x-stellaris.livewire.forms.side-to-side.section title="Informace o dopravě">
        <x-stellaris.livewire.forms.side-to-side.field label="Doprava odkud">
            <flux:input wire:model="form.transportFrom"/>
        </x-stellaris.livewire.forms.side-to-side.field>

        <x-stellaris.livewire.forms.side-to-side.field label="Doprava kam">
            <flux:input wire:model="form.transportTo"/>
        </x-stellaris.livewire.forms.side-to-side.field>

        <x-stellaris.livewire.forms.side-to-side.field label="Sazba dopravy (Kč)">
            <flux:input type="number" step="1" wire:model="form.transportRateInCzk"/>
        </x-stellaris.livewire.forms.side-to-side.field>

        <x-stellaris.livewire.forms.side-to-side.field label="Vzdálenost (km)">
            <flux:input type="number" step="0.01" wire:model="form.transportDistanceInKm"/>
        </x-stellaris.livewire.forms.side-to-side.field>
    </x-stellaris.livewire.forms.side-to-side.section>
</div>
