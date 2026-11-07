<?php

namespace App\Jobs\Files;

use App\Models\File;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DeleteStoredFile implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(public string $path, public string $disk) {}

    public function backoff(): array
    {
        return [10, 30, 120];
    }

    public function handle(): void
    {
        if (File::query()->where('path', $this->path)->exists()) {
            return;
        }

        if (! Storage::disk($this->disk)->delete($this->path)) {
            throw new RuntimeException('Could not delete the orphaned stored file.');
        }
    }
}
