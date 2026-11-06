<?php

namespace App\Livewire\Forms;

use App\Actions\Files\UploadFile;
use App\Enums\FileTypes;
use App\Models\Quote;
use App\Models\Work;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Form;
use Throwable;

class SingleFileUploadForm extends Form
{
    public $file = null;

    public FileTypes $fileType = FileTypes::UNKNOWN;

    public function rules(): array
    {
        $mimes = implode(',', config('app.files.mimes'));
        $maxSize = config('app.files.max_size');

        return [
            'file' => ['required', 'file', "mimes:$mimes", "max:$maxSize"],
            'fileType' => ['required', Rule::enum(FileTypes::class)],
        ];
    }

    /**
     * @throws Throwable
     */
    public function submit(Quote|Work $parent, UploadFile $uploadFile): void
    {
        Gate::authorize('uploadFile', $parent);
        $this->validate();

        try {
            $uploadFile->handle($this->file, $parent, $this->fileType);
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(
                Arr::prependKeysWith($exception->errors(), $this->getPropertyName() . '.'),
            );
        }
    }
}
