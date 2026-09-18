<?php

namespace App\Livewire\Quotes;

use App\Actions\Files\CreateQuoteSummaryPdfFile;
use App\Models\RequestedQuotes;
use Exception;
use Livewire\Component;

class QuoteSummaryButton extends Component
{
    public RequestedQuotes $quote;

    public function mount(RequestedQuotes $quote): void
    {
        $this->quote = $quote;
    }
    public function render()
    {
        return view('livewire.quote-summary-button');
    }

    /**
     * @throws Exception
     */
    public function downloadPdf()
    {
        app(CreateQuoteSummaryPdfFile::class)->handle($this->quote);
    }
}
