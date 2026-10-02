<li class="flex-col items-center justify-between py-4 pr-5 pl-4 text-sm/6">
    <div class="flex items-center justify-between">
        <div class="flex w-0 flex-1 items-center">
            <svg viewBox="0 0 20 20" fill="currentColor" data-slot="icon" aria-hidden="true"
                 class="size-5 shrink-0 text-gray-400 dark:text-gray-500">
                <path d="M15.621 4.379a3 3 0 0 0-4.242 0l-7 7a3 3 0 0 0 4.241 4.243h.001l.497-.5a.75.75 0 0 1 1.064 1.057l-.498.501-.002.002a4.5 4.5 0 0 1-6.364-6.364l7-7a4.5 4.5 0 0 1 6.368 6.36l-3.455 3.553A2.625 2.625 0 1 1 9.52 9.52l3.45-3.451a.75.75 0 1 1 1.061 1.06l-3.45 3.451a1.125 1.125 0 0 0 1.587 1.595l3.454-3.553a3 3 0 0 0 0-4.242Z"
                      clip-rule="evenodd" fill-rule="evenodd"/>
            </svg>
            <div class="ml-4 flex min-w-0 flex-1 gap-2">
            <span class="truncate font-medium text-gray-900 dark:text-gray-100">
                {{ $file_type->label() }}
            </span>
            </div>
        </div>
        <div class="ml-4 shrink-0">
            <flux:dropdown>
                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal">
                </flux:button>
                <flux:menu>
                    <flux:menu.item icon="arrow-down-tray" wire:click="downloadFile()">
                        Stáhnout
                    </flux:menu.item>
                    <flux:menu.separator/>
                    <flux:menu.submenu heading="Změnit typ">
                        @foreach(App\Enums\FileTypes::cases() as $_fileType)
                            <flux:menu.item wire:click="changeFileType('{{ $_fileType->value }}')">
                                {{ $_fileType->label() }}
                            </flux:menu.item>
                        @endforeach
                    </flux:menu.submenu>
                    <flux:menu.separator/>
                    <flux:menu.item variant="danger" icon="trash" disabled>
                        Smazat
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>
    <div class="mt-6">
        @if ( str_starts_with($file->mime_type, 'image/') )
            <img
                    src="{{ route('quotes.files.preview', [$quote, $file]) }}"
                    alt="{{ $file_type->label() }}"
                    class="max-h-[75vh] w-full object-contain"
            >
        @elseif ($file->mime_type === 'application/pdf')
            <iframe
                    src="{{ route('quotes.files.preview', [$quote, $file]) }}"
                    class="h-[75vh] w-full rounded-lg border"
            ></iframe>
        @endif
    </div>
</li>