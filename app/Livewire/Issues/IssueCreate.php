<?php

namespace App\Livewire\Issues;

use App\Enums\Permissions;
use App\Enums\Roles;
use App\Livewire\Forms\Issue\IssueForm;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Throwable;

class IssueCreate extends Component
{
    public IssueForm $form;

    public function mount(?int $customerId = null): void
    {
        Gate::authorize('create', Issue::class);

        if ($customerId !== null) {
            abort_unless(auth()->user()->can(Permissions::ISSUE_CREATE_ALL->label()) || $customerId === auth()->id(), 403);
            $this->form->userId = User::query()->role(Roles::CUSTOMER->value)->findOrFail($customerId)->id;
        } elseif (! auth()->user()->can(Permissions::ISSUE_CREATE_ALL->label())) {
            $this->form->userId = auth()->id();
        }
    }

    /** @throws Throwable */
    public function submit(): void
    {
        $issue = $this->form->submit();
        $this->redirect(route('issues.show', $issue));
    }

    public function render(): View
    {
        return view('livewire.issues.issue-create');
    }
}
