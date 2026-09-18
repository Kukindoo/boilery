<?php

namespace App\Livewire\Quotes;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\RequestedQuotes;
use Illuminate\Support\Facades\Storage;

class RequestedQuoteFilePreviewController extends Controller
{
    public function __invoke(
        RequestedQuotes $quote,
        File $file,
    ) {
        abort_unless($file->quote_id === $quote->id, 404);

        abort_unless(
            Storage::disk(config('filesystems.default'))->exists($file->path),
            404
        );

        return Storage::disk(config('filesystems.default'))->response(
            $file->path,
            $file->label,
            [
                'Content-Type' => $file->mime_type,
                'Content-Disposition' => 'inline',
            ]
        );
    }
}
