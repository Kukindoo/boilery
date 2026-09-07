<form wire:submit="submit" class="px-6 pt-20 pb-24 sm:pb-32 lg:px-8 lg:py-48">
    <div class="mx-auto max-w-xl lg:mr-0 lg:max-w-lg">
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
                <flux:file-upload wire:model="form.fileLabel" label="Štítek z boileru">
                    @if (is_null($form->fileLabel))
                        <flux:file-upload.dropzone
                                heading="Zde nahrejte štítek z boileru"
                                text="JPEG, PNG, PDF up to 10MB"
                                with-progress
                        />
                    @endif
                </flux:file-upload>

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
                <flux:file-upload wire:model="form.fileReceipt" label="Dokrad o nákupu">
                    @if (is_null($form->fileReceipt))
                        <flux:file-upload.dropzone
                                heading="Zde nahrejte doklad o nákupu"
                                text="JPEG, PNG, PDF up to 10MB"
                                with-progress
                        />
                    @endif
                </flux:file-upload>

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
                    <flux:radio value="yes" label="Yes" />
                    <flux:radio value="no" label="No" />
                </flux:radio.group>
            </div>

            @if($form->boilerUnderWarranty == 'yes')
                <div class="sm:col-span-2">
                        <flux:file-upload wire:model="form.fileWarrantyDocument" label="Záruční list">
                            @if (is_null($form->fileWarrantyDocument))
                            <flux:file-upload.dropzone
                                    heading="Zde nahrejte záruční list"
                                    text="JPEG, PNG, PDF up to 10MB"
                                    with-progress
                            />
                            @endif
                        </flux:file-upload>

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
        <div class="mt-8 flex justify-end">
            <button type="submit" class="rounded-md bg-indigo-600 px-3.5 py-2.5 text-center text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 dark:bg-indigo-500 dark:hover:bg-indigo-400 dark:focus-visible:outline-indigo-500">Send message</button>
        </div>
    </div>
</form>