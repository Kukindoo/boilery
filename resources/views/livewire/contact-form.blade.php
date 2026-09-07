<form id="contact-quote-form" wire:submit="submit" class="px-6 pt-20 pb-24 sm:pb-32 lg:px-8 lg:py-48">
    <div class="mx-auto max-w-xl lg:mr-0 lg:max-w-lg">
        @if(! $submitted)
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
            <button type="submit" class="rounded-md bg-indigo-600 px-3.5 py-2.5 text-center text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 dark:bg-primary-dark-button-bg dark:hover:bg-primary-dark-button-bg/50 dark:focus-visible:outline-primary-dark-button-bg">Send message</button>
        </div>
        @else
            <div class="rounded-md bg-green-50 p-4 dark:bg-green-500/10 dark:outline dark:outline-green-500/20">
                <div class="flex">
                    <div class="shrink-0">
                        <svg viewBox="0 0 20 20" fill="currentColor" data-slot="icon" aria-hidden="true" class="size-5 text-green-400">
                            <path d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" fill-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-green-800 dark:text-green-200">Poptávka přijata</h3>
                        <div class="mt-2 text-sm text-green-700 dark:text-green-200/85">
                            <p>Vaše potávka byla přijata. Měli by jste dostat potvrzující e-mail.</p>
                        </div>
                        <div class="mt-4">
                            <div class="-mx-2 -my-1.5 flex">
                                <p class="rounded-md bg-green-50 px-2 py-1.5 text-sm font-medium text-green-800  dark:bg-transparent dark:text-green-200">
                                    Brzy se Vám ozveme
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</form>