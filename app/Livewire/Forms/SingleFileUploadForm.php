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
        return [
            'file' => ['required', 'file', 'mimes:jpg, png,jpeg', 'max:10240'],
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
            FileTypes::BOILER_LABEL);
    }
}
