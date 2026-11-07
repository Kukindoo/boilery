@php
    use App\Contracts\OwnsFiles;
    use App\Enums\FileTypes;
    use Illuminate\Database\Eloquent\Model;
@endphp

@props([
    'model',
    'fileType',
])

@php
    /** @var Model&OwnsFiles $model */
    /** @var string $fileType */

    $triggerName = match ($fileType) {
        FileTypes::SERVICE_PROTOCOL->value => 'Servisního protokolu',
        FileTypes::QUOTE_SUMMARY->value => 'Shrnutí poptávky',
        default => 'PDF',
    };
@endphp

<flux:modal.trigger name="pdf-preview">
    <flux:button size="sm" icon="document-text">
        Náhled {{ $triggerName ?? 'PDF' }}
    </flux:button>
</flux:modal.trigger>

<flux:modal name="pdf-preview" class="w-full max-w-5xl">
    <div class="space-y-4">
        <flux:heading size="lg">Náhled {{ $triggerName }} – {{ $model->getKey() }}</flux:heading>
        <livewire:pdf.pdf-generation :model="$model" />

        <iframe
                title="Náhled {{ $triggerName ?? 'PDF' }} {{ $model->getKey() }}"
                class="h-[75vh] w-full rounded-lg border border-zinc-200 bg-white dark:border-zinc-700"
                sandbox=""
                srcdoc="{{ $pdfContent }}"
        ></iframe>

        <div class="flex justify-end">
            <flux:modal.close>
                <flux:button>
                    Zavřít
                </flux:button>
            </flux:modal.close>
        </div>
    </div>
</flux:modal>