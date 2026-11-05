<?php

namespace App\Livewire\Works;

use App\Actions\Files\DeleteFile;
use App\Enums\FileTypes;
use App\Models\File;
use App\Models\Work;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WorkFileCard extends Component
{
    #[Locked]
    public Work $work;

    #[Locked]
    public File $file;

    public function mount(Work $work, File $file): void
    {
        Gate::authorize('view', $work);
        abort_unless($file->work_id === $work->id, 404);
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

    public function changeFileType(FileTypes $file_type): void
    {
        Gate::authorize('update', $this->work);
        $file = $this->work->files()->findOrFail($this->file->id);
        Gate::authorize('update', $file);
        $file->update(['file_type' => $file_type]);
        $this->file->refresh();
    }

    public function deleteFile(DeleteFile $deleteFile): void
    {
        Gate::authorize('view', $this->work);
        $file = $this->work->files()->findOrFail($this->file->id);
        $deleteFile->handle($file);
        $this->redirect(route('works.show', $this->work), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.works.work-file-card');
    }
}
