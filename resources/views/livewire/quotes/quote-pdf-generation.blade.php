<div>
    @if ($quote->pdfGenerationInProgress())
        <div wire:poll.2s="checkProgress" role="status" aria-live="polite" aria-busy="true" class="w-64 max-w-full space-y-2">
            <flux:button disabled icon="clock" size="sm">
                {{ $quote->pdf_generation_status === \App\Enums\QuotePdfStatus::QUEUED ? 'PDF čeká na zpracování.' : 'PDF se generuje.' }}
            </flux:button>
        </div>
    @else
        @if ($quote->pdf_generation_status === \App\Enums\QuotePdfStatus::FAILED)
            <flux:button role="alert"  size="sm" icon="x-mark" color="red" disabled>
                PDF se nepodařilo vygenerovat. Zkuste to prosím znovu.
            </flux:button>
        @elseif ($quote->pdf_generation_status === \App\Enums\QuotePdfStatus::COMPLETED && $quote->hasQuoteSummary())
            <flux:button role="status" size="sm" icon="check" color="green" disabled>
                PDF je připraveno ke stažení v přílohách.
            </flux:button>
        @endif

        @can('generatePdf', $quote)
            <form wire:submit="generate">
                <flux:button type="submit" size="sm" icon="printer" wire:loading.attr="disabled" wire:target="generate">
                    Generovat PDF
                </flux:button>
                <flux:text wire:loading wire:target="generate" role="status">Zařazuji PDF ke zpracování…</flux:text>
            </form>
        @endcan
    @endif
</div>
