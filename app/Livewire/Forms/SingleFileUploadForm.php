<?php

namespace App\Livewire\Forms;

use App\Actions\Files\UploadQuoteFile;
use App\Enums\FileTypes;
use App\Models\Quote;
use Exception;
use Illuminate\Validation\Rule;
use Livewire\Form;

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
     * @throws Exception
     */
    public function submit(Quote $quote): void
    {
        $this->validate();

        app(UploadQuoteFile::class)->handle(
            $this->file,
            $quote,
            $this->fileType);
    }
}
