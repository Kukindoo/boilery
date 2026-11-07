<?php

namespace App\Livewire\Files;

use App\Contracts\OwnsFiles;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

class UploadFileButton extends Component
{
    #[Locked]
    public Model&OwnsFiles $model;

    public function mount(Model&OwnsFiles $model): void
    {
        Gate::authorize('view', $model);
        $this->model = $model;
    }

    public function render(): View
    {
        return view('livewire.files.upload-file-button', [
            'modalName' => 'upload-file-' . $this->getId(),
        ]);
    }
}
