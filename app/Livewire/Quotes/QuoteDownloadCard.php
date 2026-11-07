<?php

namespace App\Livewire\Quotes;

use App\Actions\Files\ChangeFileType;
use App\Actions\Files\DetachFile;
use App\Enums\FileTypes;
use App\Models\File;
use App\Models\Quote;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class QuoteDownloadCard extends Component
{
    #[Locked]
    public Quote $quote;

    #[Locked]
    public File $file;

    public ?FileTypes $file_type;

    public function mount(Quote $quote, File $file): void
    {
        $quote = Quote::query()->findOrFail($quote->id);
        Gate::authorize('view', $quote);
        $file = $quote->files()->findOrFail($file->id);

        $this->quote = $quote;
        $this->file = $file;
        $this->file_type = $file->file_type ?? FileTypes::UNKNOWN;
    }

    public function render(): View
    {
        return view('livewire.quote-download-card');
    }

    /**
     * @throws Throwable
     */
    public function changeFileType(FileTypes $file_type, ChangeFileType $changeFileType): void
    {
        $this->file = DB::transaction(function () use ($file_type, $changeFileType): File {
            $quote = Quote::query()->lockForUpdate()->findOrFail($this->quote->id);
            Gate::authorize('view', $quote);
            Gate::authorize('update', $quote);
            $file = $quote->files()->lockForUpdate()->findOrFail($this->file->id);

            return $changeFileType->handle($file, $file_type);
        }, attempts: 5);
        $this->file_type = $file_type;
    }

    public function downloadFile(): StreamedResponse
    {
        $quote = $this->authorizedQuote();
        $file = $quote->files()->findOrFail($this->file->id);
        Gate::authorize('view', $file);

        return Storage::disk()->download($file->path, $file->label);
    }

    /**
     * @throws Throwable
     */
    public function deleteFile(DetachFile $detachFile): void
    {
        $quote = $this->authorizedQuote();
        $file = $quote->files()->findOrFail($this->file->id);
        $detachFile->handle($quote, $file);
        $this->redirect(route('quotes.show', $quote));
    }

    private function authorizedQuote(): Quote
    {
        $quote = Quote::query()->findOrFail($this->quote->id);
        Gate::authorize('view', $quote);

        return $quote;
    }
}
