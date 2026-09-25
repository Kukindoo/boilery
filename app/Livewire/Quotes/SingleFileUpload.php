<?php

namespace App\Livewire\Quotes;

use App\Livewire\Forms\SingleFileUploadForm;
use App\Models\Quote;
use Exception;
use Livewire\Component;
use Livewire\WithFileUploads;

class SingleFileUpload extends Component
{
    use WithFileUploads;

    public SingleFileUploadForm $form;

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
     * @throws Exception
     */
    public function submit()
    {
        $this->form->submit($this->quote);
    }
}
