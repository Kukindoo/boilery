@props(['model'])

<x-stellaris.container.show-card :title="__('Přílohy')">
    <x-slot:action>
        @can('uploadFile', $model)
            <div class="ml-4 shrink-0 print:hidden">
                <flux:modal.trigger name="upload-file">
                    <flux:button icon="arrow-up-tray">
                        Nahrát
                    </flux:button>
                </flux:modal.trigger>
            </div>
        @endcan
    </x-slot:action>

    <div class="min-w-0 sm:col-span-3">
        @if($model->files()->count() === 0)
            Bez Přílohy
        @else
            <ul role="list"
                class="divide-y space-y-4 divide-subtle-border rounded-md border border-primary-border dark:divide-primary-dark-border/5 dark:border-primary-dark-border/10">
                @foreach($model->files as $file)
                    <livewire:files.file-preview-card :model="$model" :file="$file" :key="'model-file-'.$file->id"/>
                @endforeach
            </ul>
        @endif
    </div>
</x-stellaris.container.show-card>

@can('uploadFile', $model)
    <flux:modal name="upload-file" class="w-full max-w-5xl">
        <div class="space-y-6">
            <flux:heading size="lg">
                Nahrát soubor
            </flux:heading>

            <livewire:files.single-file-upload :model="$model"/>
        </div>
    </flux:modal>
@endcan