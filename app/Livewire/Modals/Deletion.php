<?php

namespace App\Livewire\Modals;

use App\Actions\DeleteModel;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Modelable;
use Livewire\Component;
use Throwable;

class Deletion extends Component
{
    #[Modelable]
    public bool $show = false;

    #[Locked]
    public ?Model $model = null;

    #[Locked]
    public string $heading;

    #[Locked]
    public string $text;

    #[Locked]
    public ?string $modalName = null;

    #[Locked]
    public ?string $triggerLabel = null;

    #[Locked]
    public string $confirmLabel = 'Smazat';

    #[Locked]
    public string $cancelLabel = 'Zrušit';

    public function confirmDelete(): void
    {
        if ($this->model !== null) {
            $this->model = $this->model->newQuery()->findOrFail($this->model->getKey());
            Gate::authorize('delete', $this->model);
        }

        $this->show = true;
    }

    /** @throws Throwable */
    public function delete(DeleteModel $deleteModel): void
    {
        if (! $this->show) {
            return;
        }

        if ($this->model === null) {
            $this->show = false;
            $this->dispatch('confirmed');

            return;
        }

        $this->model = $this->model->newQuery()->findOrFail($this->model->getKey());
        Gate::authorize('delete', $this->model);

        $deleteModel->handle($this->model);
        $this->show = false;
        $this->dispatch('deleted');
    }

    public function render(): View
    {
        return view('livewire.modals.deletion');
    }
}
