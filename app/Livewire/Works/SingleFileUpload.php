<?php

namespace App\Livewire\Works;

use App\Actions\Files\UploadFile;
use App\Livewire\Forms\SingleFileUploadForm;
use App\Models\Work;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class SingleFileUpload extends Component
{
    use WithFileUploads;

    public SingleFileUploadForm $form;

    #[Locked]
    public Work $work;

    public function mount(Work $work): void
    {
        Gate::authorize('view', $work);
        $this->work = $work;
    }

    /** @throws Throwable */
    public function submit(UploadFile $uploadFile): void
    {
        $this->form->submit($this->work, $uploadFile);
        Flux::modal('upload-work-file')->close();
        $this->form->reset();
        $this->redirect(route('works.show', $this->work), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.quotes.single-file-upload');
    }
}
