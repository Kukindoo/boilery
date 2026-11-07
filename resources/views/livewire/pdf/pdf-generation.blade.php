@php
    use App\Contracts\OwnsFiles;
    use App\Enums\PdfGenerationStatus;
    use Illuminate\Database\Eloquent\Model;
@endphp

@php
    /** @var Model&OwnsFiles $model */
    /** @var string $pdfContent */
@endphp

<div>
    @if ($inProgress)
        <div wire:poll.2s="checkProgress" role="status" aria-live="polite" aria-busy="true"
             class="w-64 max-w-full space-y-2">
            <flux:button disabled icon="clock" size="sm">
                {{ $model->pdf_generation_status === PdfGenerationStatus::QUEUED ? 'PDF čeká na zpracování.' : 'PDF se generuje.' }}
            </flux:button>
        </div>
    @else
        @if ($model->pdf_generation_status === PdfGenerationStatus::FAILED)
            <flux:button role="alert" size="sm" icon="x-mark" color="red" disabled>
                PDF se nepodařilo vygenerovat. Zkuste to prosím znovu.
            </flux:button>
        @elseif ($model->pdf_generation_status === PdfGenerationStatus::COMPLETED && $hasPdf)
            <flux:button role="status" size="sm" icon="check" color="green" disabled>
                PDF je připraveno ke stažení v přílohách.
            </flux:button>
        @endif

        @can('generatePdf', $model)
            <form wire:submit="generate">
                <flux:button type="submit" size="sm" icon="printer" wire:loading.attr="disabled" wire:target="generate">
                    Generovat PDF
                </flux:button>
            </form>
        @endcan
    @endif
</div>
