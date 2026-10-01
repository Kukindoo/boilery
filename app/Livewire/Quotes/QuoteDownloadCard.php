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

    public function changeFileType(FileTypes $file_type): void
    {
        if (! auth()->user()->can('update', $this->quote)) {
            abort(403);
        }

        if (! in_array($file_type->value, array_column(FileTypes::cases(), 'value'))) {
            abort(405);
        }

        $this->file->update([
            'file_type' => $file_type,
        ]);

        $this->file_type = $file_type;
    }

    public function downloadFile(): StreamedResponse
    {
        return Storage::disk(config('filesystems.default'))->download(
            $this->file->path,
            $this->file->label,
        );
    }
}
