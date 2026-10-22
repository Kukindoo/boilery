<?php

namespace App\Livewire\Works;

use App\Livewire\Forms\Work\WorkForm;
use App\Models\Work;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class WorkCreate extends Component
{
    public WorkForm $form;

    public function mount(): void
    {
        Gate::authorize('create', Work::class);
        $this->form->userId = auth()->id();
    }

    public function submit(): void
    {
        $work = $this->form->submit();
        $this->redirect(route('works.show', $work));
    }

    public function render(): View
    {
        return view('livewire.works.work-create');
    }
}
