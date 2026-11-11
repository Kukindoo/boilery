<?php

namespace App\Livewire\Files;

use App\Actions\Files\UploadFile;
use App\Contracts\OwnsFiles;
use App\Livewire\Forms\Files\SingleFileUploadForm;
use App\Models\Issue;
use App\Models\Quote;
use Flux\Flux;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class SingleFileUpload extends Component
{
    use WithFileUploads;

    public SingleFileUploadForm $form;

    #[Locked]
    public Model&OwnsFiles $model;

    #[Locked]
    public string $modalName = 'upload-file';

    public function mount(Model&OwnsFiles $model, string $modalName = 'upload-file'): void
    {
        $this->model = $model;
        $this->modalName = $modalName;
    }

    public function render()
    {
        return view('livewire.files.single-file-upload');
    }

    /**
     * @throws Throwable
     */
    public function submit(UploadFile $uploadFile): void
    {
        $this->form->submit($this->model, $uploadFile);
        Flux::modal($this->modalName)->close();
        $this->form->reset();

        $route = match (true) {
            $this->model instanceof Quote => 'quotes.show',
            $this->model instanceof Issue => 'issues.show',
            $this->model instanceof OwnsFiles => 'works.show',
        };

        $this->redirect(route($route, $this->model));
    }
}
