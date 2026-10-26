<?php

namespace App\Livewire\Quotes;

use App\Livewire\Forms\SingleFileUploadForm;
use App\Models\Quote;
use Exception;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
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
        if (! auth()->user()->can('update', $this->quote)) {
            abort(403);
        }

        if (! auth()->user()->can('update', $this->file)) {
            abort(403);
        }

        $this->form->submit($this->quote);
        Flux::modal('upload-file')->close();
        $this->form->reset();
        $this->redirect(route('quotes.show', $this->quote));
    }
}
