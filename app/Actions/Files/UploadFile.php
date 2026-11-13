<?php

namespace App\Actions\Files;

use App\Contracts\OwnsFiles;
use App\Enums\FileTypes;
use App\Models\File;
use App\Models\Issue;
use App\Models\Quote;
use App\Models\Work;
use App\Traits\LogsActionActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

readonly class UploadFile
{
    use LogsActionActivity;

    public function __construct(private GenerateFileLabel $generateFileLabel) {}

    /** @throws Throwable */
    public function handle(UploadedFile $file, Model&OwnsFiles $parent, FileTypes $fileType): File
    {
        return $this->store($file, $parent, $fileType, authorize: true);
    }

    /**
     * Trusted entry point for attachments on a newly submitted public quote.
     *
     * @throws Throwable
     */
    public function forPublicQuote(UploadedFile $file, Quote $quote, FileTypes $fileType): File
    {
        return $this->store($file, $quote, $fileType, authorize: false);
    }

    /** @throws Throwable */
    private function store(UploadedFile $file, Model&OwnsFiles $parent, FileTypes $fileType, bool $authorize): File
    {
        $path = null;
        $disk = Storage::disk();
        try {
            // Check early, and reauthorize the locked record before persisting.
            if ($authorize) {
                Gate::authorize('uploadFile', $parent);
            }
            Gate::authorize('uploadType', [File::class, $fileType]);

            $extension = $file->getClientOriginalExtension();
            $path = $file->store(config('app.files.attachments_save_directory'));
            if ($path === false) {
                throw new RuntimeException('Could not store the uploaded file.');
            }

            $storedFile = DB::transaction(function () use ($file, $parent, $fileType, $authorize, $path, $extension): File {
                /** @var Quote|Work|Issue $lockedParent */
                $lockedParent = $parent->newQuery()->lockForUpdate()->findOrFail($parent->getKey());
                $issue = $lockedParent instanceof Issue
                    ? $lockedParent
                    : $lockedParent->issue()->lockForUpdate()->firstOrFail();
                if ($authorize) {
                    Gate::authorize('uploadFile', $lockedParent);
                }
                Gate::authorize('uploadType', [File::class, $fileType]);
                $customer = $lockedParent->user;
                if ($customer === null) {
                    throw ValidationException::withMessages(['file' => 'Nejprve přiřaďte zákazníka.']);
                }

                $storedFile = $issue->files()->make([
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'extension' => $extension,
                    'mime_type' => $file->getMimeType(),
                    'file_type' => $fileType->value,
                    'size' => $file->getSize(),
                ]);

                $storedFile->label = $this->generateFileLabel->handle($customer, $fileType, $storedFile);
                $issue->files()->save($storedFile);

                $this->logActionCompleted('issues', $issue, 'File uploaded', [
                    'file_id' => $storedFile->id,
                    'original_name' => $storedFile->original_name,
                    'file_type' => $fileType->value,
                    'issue_id' => $issue->id,
                ]);

                return $storedFile;
            }, attempts: 5);

            if (DB::transactionLevel() > 0) {
                DB::afterRollBack(fn () => $disk->delete($path));
            }

            return $storedFile;
        } catch (Throwable $exception) {
            if (is_string($path)) {
                $disk->delete($path);
            }
            $this->logActionAttempt('files', null, $exception, [
                'fileable_type' => $parent->getMorphClass(), 'fileable_id' => $parent->getKey(),
            ]);

            throw $exception;
        }
    }
}
