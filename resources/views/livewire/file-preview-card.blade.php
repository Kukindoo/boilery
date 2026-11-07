<li class="flex-col items-center justify-between py-4 pr-5 pl-4 text-sm/6">
    <div class="flex items-center justify-between">
        <div class="flex w-0 flex-1 items-center">
            <flux:icon name="paper-clip" class="size-5 shrink-0 text-subtle-text dark:text-muted-text" />
            <div class="ml-4 flex min-w-0 flex-1 gap-2">
            <span class="truncate font-medium text-heading-text dark:text-secondary-dark-text">
                {{ $file->file_type->label() }}
            </span>
                <span class="truncate text-subtle-text">
                    {{ $file->label }}
                </span>
            </div>
        </div>
        <div class="ml-4 shrink-0">
            <x-files.files-action-dropdown :file="$file" :model="$model"/>
        </div>
    </div>
    <div class="mt-6">
        <x-files.file-preview :file="$file" :model="$model" />
    </div>
</li>