@props([
    'file',
    'model',
])

@php
    /** @var \App\Models\File $file */
@endphp

<flux:dropdown>
    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal">
    </flux:button>
    <flux:menu>
        <flux:menu.item icon="arrow-down-tray" wire:click="downloadFile()">
            Stáhnout
        </flux:menu.item>
        <flux:menu.separator/>
        @can('detach', [$file, $model])
            <flux:modal.trigger name="delete-attachment-{{ $file->id }}">
                <flux:menu.item variant="danger" icon="trash">
                    Odebrat
                </flux:menu.item>
            </flux:modal.trigger>
        @endcan
        <flux:menu.separator/>
        @can('update', $model)
            @can('update', $file)
                <flux:menu.submenu heading="Změnit typ">
                    @foreach(App\Enums\FileTypes::cases() as $_fileType)
                        <flux:menu.item wire:click="changeFileType('{{ $_fileType->value }}')">
                            {{ $_fileType->label() }}
                        </flux:menu.item>
                    @endforeach
                </flux:menu.submenu>
            @endcan
        @endcan
    </flux:menu>
</flux:dropdown>

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