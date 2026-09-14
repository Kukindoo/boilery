<?php

namespace App\Livewire\Quotes;

use App\Models\RequestedQuotes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuoteDownloadCard extends Component
{
    public RequestedQuotes $quote;

    public string $label;

    public function mount(RequestedQuotes $quote, string $label): void
    {
        $this->quote = $quote;
        $this->label = $label;
    }

    public function render()
    {
        return view('livewire.quote-download-card');
    }

    public function downloadFile(): StreamedResponse
    {
        $extension = pathinfo(
            $this->quote->label_file_path,
            PATHINFO_EXTENSION
        );

        $fileNameDirty = implode('_', [
            $this->quote->first_name,
            $this->quote->last_name,
            Str::snake($this->label)]);

        $fileName = Str::ascii($fileNameDirty) . '.' . $extension;

        return Storage::disk('local')->download(
            $this->quote->label_file_path,
            $fileName,
        );
    }
}
