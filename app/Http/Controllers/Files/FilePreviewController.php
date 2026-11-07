<?php

namespace App\Http\Controllers\Files;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Quote;
use App\Models\Work;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FilePreviewController extends Controller
{
    public function __invoke(
        string $modelType,
        string $model,
        File $file,
    ): StreamedResponse {
        $model = match ($modelType) {
            'quote' => Quote::query()->findOrFail($model),
            'work' => Work::query()->findOrFail($model),
            default => null,
        };

        if ($model === null) {
            abort(404);
        }

        if (! auth()->user()->can('view', $model)) {
            abort(403);
        }

        if (! auth()->user()->can('view', $file)) {
            abort(403);
        }

        abort_unless($file->isAttachedTo($model), 404);

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
                'X-Frame-Options' => 'SAMEORIGIN',
            ]
        );
    }
}
