<?php

namespace App\Actions\Works;

use App\Enums\FileTypes;
use App\Models\File;
use App\Models\Work;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\LaravelPdf\Facades\Pdf;
use Throwable;

class GeneratePdf
{
    /**
     * @throws Throwable
     */
    public function handle(Work $work): File
    {
        $work->loadMissing('user');
        $label = Str::ascii($work->order_id . '_' . FileTypes::SERVICE_PROTOCOL->snake()) . '.pdf';
        $directory = config('app.files.attachments_save_directory');
        $path = mb_rtrim($directory, '/') . '/' . Str::uuid() . '.pdf';
        $disk = Storage::disk();

        $content = Pdf::view('works.pdfs.service-protocol', [
            'work' => $work,
            'customer' => [
                'name' => $work->user?->name,
                'address' => $work->user?->address,
                'phone' => $work->user?->phone,
            ],
        ])->format('a4')->generatePdfContent();

        if (! $disk->put($path, $content)) {
            throw new RuntimeException('Could not store the service protocol PDF.');
        }

        try {
            return $work->files()->create([
                'path' => $path,
                'original_name' => $label,
                'extension' => 'pdf',
                'mime_type' => 'application/pdf',
                'file_type' => FileTypes::SERVICE_PROTOCOL,
                'size' => mb_strlen($content, '8bit'),
                'label' => $label,
            ]);
        } catch (Throwable $exception) {
            $disk->delete($path);

            throw $exception;
        }
    }
}
