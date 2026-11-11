<?php

namespace App\Livewire\Issues;

use App\Actions\Issues\CreateIssueQuote;
use App\Actions\Issues\CreateIssueWork;
use App\Models\Issue;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Throwable;

class IssueRecordCreate extends Component
{
    #[Locked]
    public Issue $issue;

    #[Locked]
    public string $type;

    public function mount(Issue $issue, string $type): void
    {
        Gate::authorize('view', $issue);
        abort_unless(in_array($type, ['quote', 'work'], true), 404);
        $this->issue = $issue;
        $this->type = $type;
    }

    /** @throws Throwable */
    public function create(): void
    {
        $record = match ($this->type) {
            'quote' => app(CreateIssueQuote::class)->handle($this->issue),
            'work' => app(CreateIssueWork::class)->handle($this->issue),
        };

        $this->redirectRoute($this->type === 'quote' ? 'quotes.show' : 'works.show', $record);
    }

    public function render(): View
    {
        return view('livewire.issues.issue-record-create');
    }
}
