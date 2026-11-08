@php
    use App\Contracts\OwnsFiles;
    use App\Enums\FileTypes;
    use App\Models\File;
    use Illuminate\Database\Eloquent\Model;
@endphp

@props([
    'file',
    'model',
])

@php
    /** @var File $file */
    /** @var Model&OwnsFiles $model */
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
            @can('changeType', $file)
                <flux:menu.submenu heading="Změnit typ">
                    @foreach(FileTypes::cases() as $_fileType)
                        <flux:menu.item wire:click="changeFileType('{{ $_fileType->value }}')">
                            {{ $_fileType->label() }}
                        </flux:menu.item>
                    @endforeach
                </flux:menu.submenu>
            @endcan
        @endcan
    </flux:menu>
</flux:dropdown>

<x-files.modals.detach-attachment :file="$file" :model="$model"/>
