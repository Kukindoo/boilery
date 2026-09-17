<?php

namespace App\Livewire\Quotes;

use App\Enums\FileTypes;
use App\Models\File;
use App\Models\RequestedQuotes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuoteDownloadCard extends Component
{
    public RequestedQuotes $quote;

    public File $file;

    public FileTypes $file_type;

    public function mount(RequestedQuotes $quote, File $file): void
    {
        $this->quote = $quote;
        $this->file = $file;
        $this->file_type = FileTypes::from($this->file->file_type);
    }

    public function render()
    {
        return view('livewire.quote-download-card');
    }

    public function downloadFile(): StreamedResponse
    {
        $extension = pathinfo(
            $this->quote->{$this->column},
            PATHINFO_EXTENSION
        );

        $fileNameDirty = implode('_', [
            $this->quote->first_name,
            $this->quote->last_name,
            $this->quote->id,
            Str::snake($this->label)]);

        $fileName = Str::ascii($fileNameDirty) . '.' . $extension;

        return Storage::disk('local')->download(
            $this->quote->{$this->column},
            $fileName,
        );
    }
}
