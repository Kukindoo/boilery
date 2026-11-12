@props(['model'])

<x-stellaris.panel.base :header="__('Přílohy')">
    <x-slot:action>
        <livewire:files.upload-file-button :model="$model" />
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
</x-stellaris.panel.base>
