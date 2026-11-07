<?php

namespace App\Actions\Files;

use App\Contracts\OwnsFiles;
use App\Enums\FileTypes;
use App\Models\File;
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

            $extension = $file->getClientOriginalExtension();
            $path = $file->store(config('app.files.attachments_save_directory'));
            if ($path === false) {
                throw new RuntimeException('Could not store the uploaded file.');
            }

            $storedFile = DB::transaction(function () use ($file, $parent, $fileType, $authorize, $path, $extension): File {
                if ($parent instanceof Quote) {
                    $quote = Quote::query()->lockForUpdate()->findOrFail($parent->id);
                    $work = $quote->work()->withTrashed()->lockForUpdate()->first();
                    $lockedParent = $quote;
                } else {
                    // Discover the quote first so both upload directions use the same lock order.
                    $snapshot = Work::query()->findOrFail($parent->getKey());
                    $quote = $snapshot->quote_id === null ? null : Quote::withTrashed()->lockForUpdate()->findOrFail($snapshot->quote_id);
                    $work = Work::query()->lockForUpdate()->findOrFail($parent->getKey());
                    if ($work->quote_id !== $quote?->id) {
                        throw ValidationException::withMessages(['file' => 'Přiřazená poptávka se změnila. Zkuste soubor nahrát znovu.']);
                    }
                    $lockedParent = $work;
                }
                if ($authorize) {
                    Gate::authorize('uploadFile', $lockedParent);
                }
                $customer = $lockedParent->user;
                if ($customer === null) {
                    throw ValidationException::withMessages(['file' => 'Nejprve přiřaďte zákazníka.']);
                }
                $label = $this->generateFileLabel->handle($customer, $lockedParent->id, $fileType, $extension);
                $storedFile = $lockedParent->files()->create([
                    'path' => $path, 'label' => $label,
                    'original_name' => $file->getClientOriginalName(),
                    'extension' => $extension, 'mime_type' => $file->getMimeType(),
                    'file_type' => $fileType->value, 'size' => $file->getSize(),
                ]);
                foreach ([$quote, $work] as $related) {
                    if ($related !== null && ! $related->is($lockedParent)) {
                        $related->files()->syncWithoutDetaching([$storedFile->id]);
                    }
                }
                $this->logActionCompleted('files', $storedFile, 'File uploaded', [
                    'quote_id' => $quote?->id, 'work_id' => $work?->id,
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
