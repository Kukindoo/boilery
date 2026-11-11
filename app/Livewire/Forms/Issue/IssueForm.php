<?php

namespace App\Livewire\Forms\Issue;

use App\Actions\Issues\CreateIssue;
use App\Actions\Issues\UpdateIssue;
use App\Dtos\Issues\IssueDto;
use App\Enums\IssueStatus;
use App\Enums\Permissions;
use App\Enums\Roles;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Form;
use Throwable;

class IssueForm extends Form
{
    #[Locked]
    public ?Issue $issue = null;

    public string $status = IssueStatus::OPEN->value;

    public ?int $userId = null;

    public ?string $startedAt = null;

    public ?string $finishedAt = null;

    public function initForm(Issue $issue): void
    {
        $this->issue = $issue;
        $this->status = $issue->status->value;
        $this->userId = $issue->user_id;
        $this->startedAt = $issue->started_at?->format('Y-m-d\TH:i');
        $this->finishedAt = $issue->finished_at?->format('Y-m-d\TH:i');
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(IssueStatus::class)],
            'startedAt' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'finishedAt' => ['nullable', 'date_format:Y-m-d\TH:i', Rule::when(filled($this->startedAt), 'after_or_equal:startedAt')],
            'userId' => ['required', 'integer', Rule::exists('users', 'id')],
        ];
    }

    /** @throws Throwable */
    public function submit(): Issue
    {
        Gate::authorize('create', Issue::class);

        $this->validate();
        $this->authorizeAssignments(true);

        try {
            return app(CreateIssue::class)->handle($this->toDto());
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(collect($exception->errors())
                ->mapWithKeys(fn (array $messages, string $field) => ['form.' . $field => $messages])->all());
        }
    }

    /** @throws Throwable */
    public function update(): Issue
    {
        abort_if($this->issue === null, 404);
        $issue = Issue::query()->findOrFail($this->issue->id);
        Gate::authorize('update', $issue);

        $this->validate();
        $this->authorizeAssignments(false);

        try {
            return app(UpdateIssue::class)->handle($issue, $this->toDto());
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(collect($exception->errors())
                ->mapWithKeys(fn (array $messages, string $field) => ['form.' . $field => $messages])->all());
        }
    }

    /** @return Collection<int, User> */
    public function availableCustomers(): Collection
    {
        return User::query()->role(Roles::CUSTOMER->value)->orderBy('name')->get(['id', 'name']);
    }

    private function authorizeAssignments(bool $creating): void
    {
        $permission = $creating ? Permissions::ISSUE_CREATE_ALL : Permissions::ISSUE_EDIT_ALL;
        abort_unless(auth()->user()->can($permission->label()) || $this->userId === auth()->id(), 403);
    }

    private function toDto(): IssueDto
    {
        return new IssueDto(
            userId: (int) $this->userId,
            startedAt: filled($this->startedAt) ? $this->startedAt : null,
            finishedAt: filled($this->finishedAt) ? $this->finishedAt : null,
            status: IssueStatus::from($this->status),
        );
    }
}
