<?php

namespace App\Livewire\Works;

use App\Actions\Works\QueuePdf;
use App\Enums\PdfGenerationStatus;
use App\Models\Work;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

class WorkPdfGeneration extends Component
{
    #[Locked]
    public Work $work;

    public function mount(Work $work): void
    {
        Gate::authorize('view', $work);
        $this->work = $work;
    }

    public function generate(QueuePdf $queuePdf): void
    {
        $queuePdf->handle($this->work);
        $this->work->refresh();
    }

    public function checkProgress(): void
    {
        Gate::authorize('view', $this->work);
        $this->work->refresh();

        if ($this->work->pdf_generation_status === PdfGenerationStatus::COMPLETED) {
            $this->redirect(route('works.show', $this->work), navigate: true);
        }
    }

    public function render(): View
    {
        Gate::authorize('view', $this->work);

        return view('livewire.works.work-pdf-generation');
    }
}
