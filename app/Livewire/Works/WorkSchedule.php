<?php

namespace App\Livewire\Works;

use App\Livewire\Forms\Work\WorkScheduleForm;
use App\Models\Work;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class WorkSchedule extends Component
{
    public WorkScheduleForm $form;

    public function mount(Work $work): void
    {
        Gate::authorize('view', $work);
        $this->form->initForm($work);
    }

    public function open(): void
    {
        Gate::authorize('update', $this->form->work);
        $this->form->initForm($this->form->work);
        $this->form->resetValidation();
        $this->modal('work-schedule-' . $this->form->work->id)->show();
    }

    public function submit(): void
    {
        $this->form->submit();
        $this->modal('work-schedule-' . $this->form->work->id)->close();
    }

    public function render(): View
    {
        Gate::authorize('view', $this->form->work);

        return view('livewire.works.work-schedule');
    }
}
