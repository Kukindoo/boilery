<?php

namespace App\Livewire\Issues;

use App\Livewire\Forms\Issue\IssueForm;
use App\Models\Issue;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Throwable;

class IssueEdit extends Component
{
    public IssueForm $form;

    public function mount(Issue $issue): void
    {
        Gate::authorize('update', $issue);

        $this->form->initForm($issue);
    }

    /** @throws Throwable */
    public function submit(): void
    {
        $issue = $this->form->update();
        $this->redirect(route('issues.show', $issue));
    }

    public function render(): View
    {
        return view('livewire.issues.issue-edit');
    }
}
