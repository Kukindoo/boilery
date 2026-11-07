<?php

namespace App\Livewire\Files;

use App\Actions\Files\ChangeFileType;
use App\Actions\Files\DetachFile;
use App\Contracts\OwnsFiles;
use App\Enums\FileTypes;
use App\Models\File;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class FilePreviewCard extends Component
{
    #[Locked]
    public Model&OwnsFiles $model;

    #[Locked]
    public File $file;

    public ?FileTypes $file_type;

    public function render()
    {
        return view('livewire.file-preview-card');
    }

    public function mount(Model&OwnsFiles $model, File $file): void
    {
        /** @var Model&OwnsFiles $model */
        $model = $model->newQuery()->findOrFail($model->getKey());
        Gate::authorize('view', $model);
        $file = $model->files()->findOrFail($file->getKey());

        $this->model = $model;
        $this->file = $file;
        $this->file_type = $file->file_type ?? FileTypes::UNKNOWN;
    }

    /**
     * @throws Throwable
     */
    public function changeFileType(FileTypes $file_type, ChangeFileType $changeFileType): void
    {
        $this->file = DB::transaction(function () use ($file_type, $changeFileType): File {
            $model = $this->model->newQuery()->lockForUpdate()->findOrFail($this->model->getKey());
            Gate::authorize('view', $model);
            Gate::authorize('update', $model);
            $file = $model->files()->lockForUpdate()->findOrFail($this->file->getKey());

            return $changeFileType->handle($file, $file_type);
        }, attempts: 5);
        $this->file_type = $file_type;
    }

    public function downloadFile(): StreamedResponse
    {
        $model = $this->authorizedModel();
        $file = $model->files()->findOrFail($this->file->getKey());
        Gate::authorize('view', $file);

        return Storage::disk()->download($file->path, $file->label);
    }

    /**
     * @throws Throwable
     */
    public function deleteFile(DetachFile $detachFile): void
    {
        $model = $this->authorizedModel();
        $file = $model->files()->findOrFail($this->file->getKey());
        $detachFile->handle($model, $file);
        $this->redirect(route('quotes.show', $model));
    }

    private function authorizedModel(): Model&OwnsFiles
    {
        $model = $this->model->newQuery()->findOrFail($this->model->getKey());
        Gate::authorize('view', $model);

        return $model;
    }
}
