<div class="space-y-2">
    <section class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800/50 dark:shadow-none dark:inset-ring dark:inset-ring-white/10">
        <div class="px-4 py-6 sm:px-6">
            <h3 class="text-base/7 font-semibold text-gray-900 dark:text-white">
                Informace o zakázce
            </h3>
        </div>
        <div class="divide-y divide-gray-100 border-t border-gray-100 dark:divide-white/5 dark:border-white/5">
            @if (auth()->user()->can($form->work ? \App\Enums\Permissions::WORK_EDIT_ALL->label() : \App\Enums\Permissions::WORK_CREATE_ALL->label()))
                <x-stellaris.livewire.forms.field-side-to-side label="ID Zákazníka">
                        <flux:input type="number" wire:model="form.userId" disabled/>
                </x-stellaris.livewire.forms.field-side-to-side>
            @endif

            <x-stellaris.livewire.forms.field-side-to-side label="ID poptávky">
                <flux:input type="number" wire:model="form.quoteId" disabled/>
            </x-stellaris.livewire.forms.field-side-to-side>

            <x-stellaris.livewire.forms.field-side-to-side label="Číslo zakázky">
                <flux:input wire:model="form.orderId" />
            </x-stellaris.livewire.forms.field-side-to-side>

            @if ($form->work)
                <x-stellaris.livewire.forms.field-side-to-side label="Status">
                    <flux:select wire:model="form.status" variant="listbox">
                        @foreach (\App\Enums\WorkStatus::cases() as $status)
                            <flux:select.option :value="$status->value">
                                {{ $status->label() }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </x-stellaris.livewire.forms.field-side-to-side>
            @endif

            <x-stellaris.livewire.forms.field-side-to-side label="Datum přijetí">
                <flux:date-picker wire:model="form.workAcceptedAt" />
            </x-stellaris.livewire.forms.field-side-to-side>

            <x-stellaris.livewire.forms.field-side-to-side label="Datum dokončení">
                <flux:date-picker wire:model="form.workCompletedAt" />
            </x-stellaris.livewire.forms.field-side-to-side>

            <x-stellaris.livewire.forms.field-side-to-side label="Jméno technika">
                <flux:input wire:model="form.mechanicName" />
            </x-stellaris.livewire.forms.field-side-to-side>
        </div>
    </section>

    <section class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800/50 dark:shadow-none dark:inset-ring dark:inset-ring-white/10">
        <div class="px-4 py-6 sm:px-6">
            <h3 class="text-base/7 font-semibold text-gray-900 dark:text-white">
                Informace o výrobku
            </h3>
        </div>
        <div class="divide-y divide-gray-100 border-t border-gray-100 dark:divide-white/5 dark:border-white/5">
            <flux:field class="px-4 py-6 sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:space-y-0 sm:px-6">
                <flux:label class="sm:pt-2">
                    Typ výrobku
                </flux:label>
                <div class="min-w-0 space-y-2 sm:col-span-2">
                    <flux:input wire:model="form.typeOfProduct" />
                    <flux:error name="form.typeOfProduct" />
                </div>
            </flux:field>
            <flux:field class="px-4 py-6 sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:space-y-0 sm:px-6">
                <flux:label class="sm:pt-2">
                    Typové číslo
                </flux:label>
                <div class="min-w-0 space-y-2 sm:col-span-2">
                    <flux:input wire:model="form.typeNumber" />
                    <flux:error name="form.typeNumber" />
                </div>
            </flux:field>
            <flux:field class="px-4 py-6 sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:space-y-0 sm:px-6">
                <flux:label class="sm:pt-2">
                    Výrobní číslo
                </flux:label>
                <div class="min-w-0 space-y-2 sm:col-span-2">
                    <flux:input wire:model="form.serialNumber" />
                    <flux:error name="form.serialNumber" />
                </div>
            </flux:field>
            <flux:field class="px-4 py-6 sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:space-y-0 sm:px-6">
                <flux:label class="sm:pt-2">
                    Datum nákupu
                </flux:label>
                <div class="min-w-0 space-y-2 sm:col-span-2">
                    <flux:input type="date" wire:model="form.dayOfPurchase" />
                    <flux:error name="form.dayOfPurchase" />
                </div>
            </flux:field>
            <flux:field class="px-4 py-6 sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:space-y-0 sm:px-6">
                <flux:label class="sm:pt-2">
                    Typ budovy
                </flux:label>
                <div class="min-w-0 space-y-2 sm:col-span-2">
                    <flux:input wire:model="form.buildingType" />
                    <flux:error name="form.buildingType" />
                </div>
            </flux:field>
            <flux:field class="px-4 py-6 sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:space-y-0 sm:px-6">
                <flux:label class="sm:pt-2">
                    Oprava v záruce
                </flux:label>
                <div class="min-w-0 space-y-2 sm:col-span-2">
                    <flux:input wire:model="form.repairInWarranty" />
                    <flux:error name="form.repairInWarranty" />
                </div>
            </flux:field>
            <flux:field class="px-4 py-6 sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:space-y-0 sm:px-6">
                <flux:label class="sm:pt-2">
                    Záruka uznána
                </flux:label>
                <div class="min-w-0 space-y-2 sm:col-span-2">
                    <flux:checkbox wire:model="form.warrantyAccepted" />
                    <flux:error name="form.warrantyAccepted" />
                </div>
            </flux:field>
        </div>
    </section>

    <section class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800/50 dark:shadow-none dark:inset-ring dark:inset-ring-white/10">
        <div class="px-4 py-6 sm:px-6">
            <h3 class="text-base/7 font-semibold text-gray-900 dark:text-white">
                Informace o opravě
            </h3>
        </div>
        <div class="divide-y divide-gray-100 border-t border-gray-100 dark:divide-white/5 dark:border-white/5">
            <flux:field class="px-4 py-6 sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:space-y-0 sm:px-6">
                <flux:label class="sm:pt-2">
                    Nahlášený problém
                </flux:label>
                <div class="min-w-0 space-y-2 sm:col-span-2">
                    <flux:input wire:model="form.reportedProblem" />
                    <flux:error name="form.reportedProblem" />
                </div>
            </flux:field>
            <flux:field class="px-4 py-6 sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:space-y-0 sm:px-6">
                <flux:label class="sm:pt-2">
                    Provedené práce
                </flux:label>
                <div class="min-w-0 space-y-2 sm:col-span-2">
                    <flux:textarea wire:model="form.workPerformedDescription" />
                    <flux:error name="form.workPerformedDescription" />
                </div>
            </flux:field>
            <flux:field class="px-4 py-6 sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:space-y-0 sm:px-6">
                <flux:label class="sm:pt-2">
                    Poznámka
                </flux:label>
                <div class="min-w-0 space-y-2 sm:col-span-2">
                    <flux:textarea wire:model="form.note" />
                    <flux:error name="form.note" />
                </div>
            </flux:field>
        </div>
    </section>

    <section class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800/50 dark:shadow-none dark:inset-ring dark:inset-ring-white/10">
        <div class="px-4 py-6 sm:px-6">
            <h3 class="text-base/7 font-semibold text-gray-900 dark:text-white">
                Doprava
            </h3>
        </div>
        <div class="divide-y divide-gray-100 border-t border-gray-100 dark:divide-white/5 dark:border-white/5">
            <flux:field class="px-4 py-6 sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:space-y-0 sm:px-6">
                <flux:label class="sm:pt-2">
                    Doprava odkud
                </flux:label>
                <div class="min-w-0 space-y-2 sm:col-span-2">
                    <flux:input wire:model="form.transportFrom" />
                    <flux:error name="form.transportFrom" />
                </div>
            </flux:field>
            <flux:field class="px-4 py-6 sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:space-y-0 sm:px-6">
                <flux:label class="sm:pt-2">
                    Doprava kam
                </flux:label>
                <div class="min-w-0 space-y-2 sm:col-span-2">
                    <flux:input wire:model="form.transportTo" />
                    <flux:error name="form.transportTo" />
                </div>
            </flux:field>
            <flux:field class="px-4 py-6 sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:space-y-0 sm:px-6">
                <flux:label class="sm:pt-2">
                    Sazba dopravy (Kč)
                </flux:label>
                <div class="min-w-0 space-y-2 sm:col-span-2">
                    <flux:input type="number" step="1" wire:model="form.transportRateInCzk" />
                    <flux:error name="form.transportRateInCzk" />
                </div>
            </flux:field>
            <flux:field class="px-4 py-6 sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:space-y-0 sm:px-6">
                <flux:label class="sm:pt-2">
                    Vzdálenost (km)
                </flux:label>
                <div class="min-w-0 space-y-2 sm:col-span-2">
                    <flux:input type="number" step="0.01" wire:model="form.transportDistanceInKm" />
                    <flux:error name="form.transportDistanceInKm" />
                </div>
            </flux:field>
        </div>
    </section>
</div>
