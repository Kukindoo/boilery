<?php

namespace App\Http\Controllers\Works;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Work;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RequestedWorkFilePreviewController extends Controller
{
    public function __invoke(
        Work $work,
        File $file,
    ): StreamedResponse {
        if (! auth()->user()->can('view', $work)) {
            abort(403);
        }

        if (! auth()->user()->can('view', $file)) {
            abort(403);
        }

        abort_unless($file->isAttachedTo($work), 404);

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
