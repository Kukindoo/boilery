<?php

namespace App\Livewire\Forms\AssemblyAction;

use App\Actions\AssemblyActions\CreateAssemblyAction;
use App\Actions\AssemblyActions\UpdateAssemblyAction;
use App\Dtos\AssemblyActions\AssemblyActionDto;
use App\Models\AssemblyAction;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Form;
use Throwable;

class AssemblyActionForm extends Form
{
    #[Locked]
    public ?AssemblyAction $assemblyAction = null;

    public string $code = '';

    public string $name = '';

    public ?string $description = null;

    public function initForm(AssemblyAction $assemblyAction): void
    {
        $this->assemblyAction = $assemblyAction;
        $this->code = $assemblyAction->code;
        $this->name = $assemblyAction->name;
        $this->description = $assemblyAction->description;
    }

    /** @throws Throwable */
    public function submit(): AssemblyAction
    {
        Gate::authorize('create', AssemblyAction::class);

        $this->code = mb_trim($this->code);
        $this->name = mb_trim($this->name);
        $this->validate();

        return app(CreateAssemblyAction::class)->handle($this->toDto());
    }

    /** @throws Throwable */
    public function update(): AssemblyAction
    {
        abort_if($this->assemblyAction === null, 404);
        $assemblyAction = AssemblyAction::query()->findOrFail($this->assemblyAction->id);
        Gate::authorize('update', $assemblyAction);

        $this->code = mb_trim($this->code);
        $this->name = mb_trim($this->name);
        $this->validate();

        return app(UpdateAssemblyAction::class)->handle($assemblyAction, $this->toDto());
    }

    /** @return array<string, list<string>> */
    protected function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
        ];
    }

    private function toDto(): AssemblyActionDto
    {
        return new AssemblyActionDto(
            name: $this->name,
            description: filled($this->description) ? mb_trim($this->description) : null,
            code: $this->code,
        );
    }
}
