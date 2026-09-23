<div class="grid grid-cols-1 gap-x-8 gap-y-6 sm:grid-cols-2">
    <flux:input wire:model="form.firstName" label="Jméno (Vyžadováno)" type="text" autocomplete="given-name"/>

    <flux:input wire:model="form.lastName" label="Přijmení (Vyžadováno)" type="text" autocomplete="last-name"/>

    <div class="sm:col-span-2">
        <flux:input wire:model="form.email" label="E-mail (Vyžadováno)" type="email" autocomplete="email"/>
    </div>

    <div class="sm:col-span-2">
        <flux:input wire:model="form.phone" label="Telefonní číslo (Vyžadováno)" type="tel" autocomplete="tel"/>
    </div>

    <div class="sm:col-span-2">
        <flux:textarea
                wire:model="form.message"
                label="Zpráva/Popis závady (Vyžadováno)"
                placeholder="Popište závadu..."
                rows="4"
        />
    </div>
    <div class="sm:col-span-2">
        <flux:textarea
                wire:model="form.address"
                label="Vaše adresa"
                placeholder="Ulice s č.p., Město, PSČ"
                rows="4"
        />
    </div>

    <div class="sm:col-span-2">
        <label for="boiler-manufacturer" class="block text-sm/6 font-semibold text-gray-900 dark:text-white">
            Výrobce bojlerů
        </label>
        <flux:select wire:model="form.boilerManufacturer" variant="listbox" placeholder="Zvolte výrobce bojlerů...">
            @foreach(\App\Enums\BoilerManufacturers::cases() as $boilerManufacturer)
                <flux:select.option value="{{ $boilerManufacturer->value }}">
                    {{ $boilerManufacturer->label() }}
                </flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="sm:col-span-2">
        <flux:input wire:model="form.boilerSerialNumber" label="Výrobní číslo bojleru" type="text"/>
    </div>

    <div class="sm:col-span-2">
        <flux:input wire:model="form.boilerType" label="Typ bojleru" type="text"/>
    </div>

    <div class="sm:col-span-2">
        @if (is_null($form->fileLabel))
            <flux:file-upload wire:model.live="form.fileLabel" label="Štítek z boileru">
                <flux:file-upload.dropzone
                        heading="Zde nahrejte štítek z boileru"
                        text="JPEG, PNG do 10MB"
                        with-progress
                />
            </flux:file-upload>
        @endif

        @if ($form->fileLabel)
            <div class="mt-4 flex flex-col gap-2">
                <flux:file-item
                        :heading="$form->fileLabel->getClientOriginalName()"
                        :size="$form->fileLabel->getSize()"
                >
                    <x-slot name="actions">
                        <flux:file-item.remove
                                wire:click="$set('form.fileLabel', null)"
                        />
                    </x-slot>
                </flux:file-item>
            </div>
        @endif
    </div>

    <div class="sm:col-span-2">
        @if (is_null($form->fileReceipt))
            <flux:file-upload wire:model.live="form.fileReceipt" label="Doklad o nákupu">
                <flux:file-upload.dropzone
                        heading="Zde nahrejte doklad o nákupu"
                        text="JPEG, PNG do 10MB"
                        with-progress
                />
            </flux:file-upload>
        @endif

        @if ($form->fileReceipt)
            <div class="mt-4 flex flex-col gap-2">
                <flux:file-item
                        :heading="$form->fileReceipt->getClientOriginalName()"
                        :size="$form->fileReceipt->getSize()"
                >
                    <x-slot name="actions">
                        <flux:file-item.remove
                                wire:click="$set('form.fileReceipt', null)"
                        />
                    </x-slot>
                </flux:file-item>
            </div>
        @endif
    </div>

    <div class="sm:col-span-2">
        <flux:radio.group
                wire:model.live="form.boilerUnderWarranty"
                label="Bojler v záruce?"
                variant="segmented"
        >
            <flux:radio value="yes" label="Ano"/>
            <flux:radio value="no" label="Ne"/>
        </flux:radio.group>
    </div>

    @if($form->boilerUnderWarranty == 'yes')
        <div class="sm:col-span-2">
            @if (is_null($form->fileWarrantyDocument))
                <flux:file-upload wire:model.live="form.fileWarrantyDocument" label="Záruční list">
                    <flux:file-upload.dropzone
                            heading="Zde nahrejte záruční list"
                            text="JPEG, PNG do 10MB"
                            with-progress
                    />
                </flux:file-upload>
            @endif

            @if ($form->fileWarrantyDocument)
                <div class="mt-4 flex flex-col gap-2">
                    <flux:file-item
                            :heading="$form->fileWarrantyDocument->getClientOriginalName()"
                            :size="$form->fileWarrantyDocument->getSize()"
                    >
                        <x-slot name="actions">
                            <flux:file-item.remove
                                    wire:click="$set('form.fileWarrantyDocument', null)"
                            />
                        </x-slot>
                    </flux:file-item>
                </div>
            @endif
        </div>
    @endif
</div>
