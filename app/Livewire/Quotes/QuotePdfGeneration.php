<?php

namespace App\Livewire\Quotes;

use App\Actions\Quotes\QueuePdf;
use App\Enums\PdfGenerationStatus;
use App\Models\Quote;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

class QuotePdfGeneration extends Component
{
    #[Locked]
    public Quote $quote;

    public function mount(Quote $quote): void
    {
        Gate::authorize('view', $quote);
        $this->quote = $quote;
    }

    public function generate(QueuePdf $queuePdf): void
    {
        $queuePdf->handle($this->quote);
        $this->quote->refresh();
    }

    public function checkProgress(): void
    {
        Gate::authorize('view', $this->quote);
        $this->quote->refresh();

        if ($this->quote->pdf_generation_status === PdfGenerationStatus::COMPLETED) {
            $this->redirect(route('quotes.show', $this->quote), navigate: true);
        }
    }

    public function render(): View
    {
        Gate::authorize('view', $this->quote);

        return view('livewire.quotes.quote-pdf-generation');
    }
}
