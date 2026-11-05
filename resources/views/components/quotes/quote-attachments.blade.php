@props(['quote'])

<div class="overflow-hidden bg-card-bg shadow-sm sm:rounded-lg dark:bg-card-dark-bg dark:shadow-none dark:inset-ring dark:inset-ring-primary-dark-border/10">
    <div class="px-4 py-6 sm:px-6 flex items-center justify-between">
        <h3 class="text-base/7 font-semibold text-heading-text dark:text-primary-dark-text">
            Přílohy
        </h3>
        @can('uploadFile', $quote)
        <div class="ml-4 shrink-0 print:hidden">
            <flux:modal.trigger name="upload-file">
                <flux:button icon="arrow-up-tray">
                    Nahrát
                </flux:button>
            </flux:modal.trigger>
        </div>
        @endcan
    </div>
    <div class="border-t border-subtle-border dark:border-primary-dark-border/5">
        <dl class="divide-y divide-subtle-border dark:divide-primary-dark-border/5">
            @if($quote->files()->count() === 0)
                <div class="px-4 py-6 sm:px-6">
                    Bez Přílohy
                    @else
                        <div class="px-4 py-6 sm:px-6">
                            <ul role="list"
                                class="divide-y space-y-4 divide-subtle-border rounded-md border border-primary-border dark:divide-primary-dark-border/5 dark:border-primary-dark-border/10">
                                @foreach($quote->files as $file)
                                    <livewire:quotes.quote-download-card
                                            :quote="$quote"
                                            :file="$file"
                                    />
                                @endforeach
                            </ul>
                        </div>
                </div>
            @endif
        </dl>
    </div>
</div>

@can('uploadFile', $quote)
<flux:modal name="upload-file" class="w-full max-w-5xl">
    <div class="space-y-6">
        <flux:heading size="lg">Nahrát soubor</flux:heading>

        <livewire:quotes.single-file-upload :quote="$quote"/>
    </div>
</flux:modal>
@endcan
