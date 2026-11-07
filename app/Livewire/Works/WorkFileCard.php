<?php

namespace App\Livewire\Works;

use App\Actions\Files\ChangeFileType;
use App\Actions\Files\DetachFile;
use App\Enums\FileTypes;
use App\Models\File;
use App\Models\Work;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class WorkFileCard extends Component
{
    #[Locked]
    public Work $work;

    #[Locked]
    public File $file;

    public function mount(Work $work, File $file): void
    {
        Gate::authorize('view', $work);
        abort_unless($file->isAttachedTo($work), 404);
        $this->work = $work;
        $this->file = $file;
    }

    public function downloadFile(): StreamedResponse
    {
        Gate::authorize('view', $this->work);
        $file = $this->work->files()->findOrFail($this->file->id);
        Gate::authorize('view', $file);

        return Storage::disk()->download($file->path, $file->label);
    }

    /**
     * @throws Throwable
     */
    public function changeFileType(FileTypes $file_type, ChangeFileType $changeFileType): void
    {
        $this->file = DB::transaction(function () use ($file_type, $changeFileType): File {
            $work = Work::query()->lockForUpdate()->findOrFail($this->work->id);
            Gate::authorize('view', $work);
            Gate::authorize('update', $work);
            $file = $work->files()->lockForUpdate()->findOrFail($this->file->id);

            return $changeFileType->handle($file, $file_type);
        }, attempts: 5);
    }

    /**
     * @throws Throwable
     */
    public function deleteFile(DetachFile $detachFile): void
    {
        $work = Work::query()->findOrFail($this->work->id);
        Gate::authorize('view', $work);
        $file = $work->files()->findOrFail($this->file->id);
        $detachFile->handle($work, $file);
        $this->redirect(route('works.show', $this->work), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.works.work-file-card');
    }
}
