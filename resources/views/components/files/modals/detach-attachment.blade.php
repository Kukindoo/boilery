@props([
    'file',
    'model',
])

@php
    /** @var \App\Models\File $file */
@endphp

@can('detach', [$file, $model])
    <flux:modal name="delete-attachment-{{ $file->id }}" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">
                    Odebrat přílohu?
                </flux:heading>
                <flux:text class="mt-2">
                    Příloha bude odebrána z této zakázky.<br>
                    Pokud soubor nemá jiné přílohy, bude odstraněn.
                </flux:text>
            </div>
            <div class="flex gap-2">
                <flux:spacer/>
                <flux:modal.close>
                    <flux:button variant="ghost">
                        Zrušit
                    </flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="danger" wire:click="deleteFile">
                    Odebrat přílohu
                </flux:button>
            </div>
        </div>
    </flux:modal>
@endcan
