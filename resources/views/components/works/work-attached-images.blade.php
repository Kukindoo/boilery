@props(['work'])

<div class="overflow-hidden bg-card-bg shadow-sm sm:rounded-lg dark:bg-card-dark-bg dark:shadow-none dark:inset-ring dark:inset-ring-primary-dark-border/10">
    <div class="flex items-center justify-between px-4 py-6 sm:px-6">
        <h3 class="text-base/7 font-semibold text-heading-text dark:text-primary-dark-text">
            Přílohy
        </h3>
        @can('uploadFile', $work)
            <flux:modal.trigger name="upload-work-file">
                <flux:button icon="arrow-up-tray">
                    Nahrát
                </flux:button>
            </flux:modal.trigger>
        @endcan
    </div>
    <div class="border-t border-subtle-border dark:border-primary-dark-border/5">
        @if ($work->files->isEmpty())
            <div class="px-4 py-6 sm:px-6">Bez Přílohy</div>
        @else
            <div class="px-4 py-6 sm:px-6">
            <ul role="list" class="divide-y space-y-4 divide-subtle-border rounded-md border border-primary-border dark:divide-primary-dark-border/5 dark:border-primary-dark-border/10">
                @foreach ($work->files as $file)
                    <livewire:files.file-preview-card :model="$work" :file="$file" :key="'work-file-'.$file->id" />
                @endforeach
            </ul>
            </div>
        @endif
    </div>
</div>

@can('uploadFile', $work)
    <flux:modal name="upload-work-file" class="w-full max-w-5xl">
        <div class="space-y-6">
            <flux:heading size="lg">
                Nahrát soubor
            </flux:heading>
            <livewire:works.single-file-upload :work="$work" />
        </div>
    </flux:modal>
@endcan
