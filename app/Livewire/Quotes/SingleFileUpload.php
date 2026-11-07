<?php

namespace App\Livewire\Quotes;

use App\Actions\Files\UploadFile;
use App\Livewire\Forms\Files\SingleFileUploadForm;
use App\Models\Quote;
use Flux\Flux;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class SingleFileUpload extends Component
{
    use WithFileUploads;

    public SingleFileUploadForm $form;

    #[Locked]
    public Quote $quote;

    public function mount(Quote $quote): void
    {
        $this->quote = $quote;
    }

    public function render()
    {
        return view('livewire.quotes.single-file-upload');
    }

    /**
     * @throws Throwable
     */
    public function submit(UploadFile $uploadFile): void
    {

        $this->form->submit($this->quote, $uploadFile);
        Flux::modal('upload-file')->close();
        $this->form->reset();
        $this->redirect(route('quotes.show', $this->quote));
    }
}
