<?php

namespace App\Livewire\Quotes;

use App\Enums\FileTypes;
use App\Models\File;
use App\Models\Quote;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuoteDownloadCard extends Component
{
    public Quote $quote;

    public File $file;

    public ?FileTypes $file_type;

    public function mount(Quote $quote, File $file): void
    {
        $this->quote = $quote;
        $this->file = $file;
        $this->file_type = $this->file->file_type ?? FileTypes::UNKNOWN;
    }

    public function render()
    {
        return view('livewire.quote-download-card');
    }

    public function downloadFile(): StreamedResponse
    {
        return Storage::disk(config('filesystems.default'))->download(
            $this->file->path,
            $this->file->label,
        );
    }
}
