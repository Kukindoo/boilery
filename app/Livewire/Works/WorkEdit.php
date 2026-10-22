<?php

namespace App\Livewire\Works;

use App\Livewire\Forms\Work\WorkForm;
use App\Models\Work;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class WorkEdit extends Component
{
    public WorkForm $form;

    public function mount(Work $work): void
    {
        Gate::authorize('update', $work);
        $this->form->initForm($work);
    }

    public function submit(): void
    {
        $work = $this->form->update();
        $this->redirect(route('works.show', $work));
    }

    public function render(): View
    {
        return view('livewire.works.work-edit');
    }
}
