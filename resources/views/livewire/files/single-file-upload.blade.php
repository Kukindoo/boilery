@php
    use App\Enums\FileTypes;
    use App\Models\File;
@endphp

<div class="px-6">
    <form id="single-file-upload-form" wire:submit="submit">
        <div class="mx-auto max-w-xl lg:mr-0 lg:max-w-lg space-y-5">
            <div class="sm:col-span-2">
                <label for="file-type" class="block text-sm/6 font-semibold text-heading-text dark:text-primary-dark-text">
                    Typ souborů
                </label>
                <flux:select wire:model="form.fileType" variant="listbox" placeholder="Zvolte typ souboru...">
                    @foreach(FileTypes::cases() as $fileType)
                        @can('uploadType', [File::class, $fileType])
                            <flux:select.option value="{{ $fileType->value }}">
                                {{ $fileType->label() }}
                            </flux:select.option>
                        @endcan
                    @endforeach
                </flux:select>
            </div>

            <div class="sm:col-span-2">
                @if (is_null($form->file))
                    <flux:file-upload wire:model.live="form.file" label="Soubor">
                        <flux:file-upload.dropzone
                                heading="Zde nahrejte soubor"
                                text="JPEG, PNG do 10MB"
                                with-progress
                        />
                    </flux:file-upload>
                @endif

                @if ($form->file)
                    <div class="mt-4 flex flex-col gap-2">
                        <flux:file-item
                                :heading="$form->file->getClientOriginalName()"
                                :size="$form->file->getSize()"
                        >
                            <x-slot name="actions">
                                <flux:file-item.remove
                                        wire:click="$set('form.file', null)"
                                />
                            </x-slot>
                        </flux:file-item>
                    </div>
                @endif
            </div>

            <div class="mt-8 flex justify-end">
                <button type="submit"
                        class="rounded-md bg-accent px-3.5 py-2.5 text-center text-sm font-semibold text-accent-foreground shadow-xs hover:bg-accent/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">
                    Nahrát
                </button>
            </div>
        </div>
    </form>
</div>