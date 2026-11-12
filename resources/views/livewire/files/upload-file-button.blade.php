<div class="ml-4 shrink-0 print:hidden">
    @can('uploadFile', $model)
        <flux:modal.trigger :name="$modalName">
            <flux:button icon="arrow-up-tray" size="sm">
                Nahrát
            </flux:button>
        </flux:modal.trigger>

        <flux:modal :name="$modalName" class="w-full max-w-5xl">
            <div class="space-y-6">
                <flux:heading size="lg">
                    Nahrát soubor
                </flux:heading>

                <livewire:files.single-file-upload :model="$model" :modal-name="$modalName" />
            </div>
        </flux:modal>
    @endcan
</div>
