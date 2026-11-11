<?php

namespace App\Livewire\Works;

use App\Livewire\Forms\Work\WorkForm;
use App\Models\Work;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Throwable;

class WorkCreate extends Component
{
    public WorkForm $form;

    public function mount(): void
    {
        Gate::authorize('create', Work::class);
        $this->form->issueId = request()->integer('issue_id') ?: null;
    }

    /** @throws Throwable */
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
