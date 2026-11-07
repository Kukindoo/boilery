<?php

namespace App\Livewire\Pdf;

use App\Actions\Pdf\QueuePdf;
use App\Contracts\OwnsFiles;
use App\Enums\PdfGenerationStatus;
use App\Models\Quote;
use App\Models\Work;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Throwable;

class PdfGeneration extends Component
{
    #[Locked]
    public Model&OwnsFiles $model;

    public function mount(Model&OwnsFiles $model): void
    {
        abort_unless($model instanceof Quote || $model instanceof Work, 404);
        Gate::authorize('view', $model);
        $this->model = $model;
    }

    /** @throws Throwable */
    public function generate(QueuePdf $queuePdf): void
    {
        $queuePdf->handle($this->model);
        $this->model->refresh();
    }

    public function checkProgress(): void
    {
        Gate::authorize('view', $this->model);
        $this->model->refresh();

        if ($this->model->pdf_generation_status === PdfGenerationStatus::COMPLETED) {
            $route = match (true) {
                $this->model instanceof Quote => 'quotes.show',
                $this->model instanceof Work => 'works.show',
                default => null,
            };

            if (is_null($route)) {
                abort(404);
            }

            Flux::toast(
                text: 'PDF bylo úspěšně vygenerováno. Najdete ho v přílohách.',
                variant: 'success',
            );
            $this->redirect(route($route, $this->model), navigate: true);
        }
    }

    public function render(): View
    {
        Gate::authorize('view', $this->model);

        $hasPdf = $this->model->pdf_generation_status === PdfGenerationStatus::COMPLETED && match (true) {
            $this->model instanceof Quote => $this->model->hasQuoteSummary(),
            $this->model instanceof Work => $this->model->hasServiceProtocol(),
            default => false,
        };

        return view('livewire.pdf.pdf-generation', [
            'inProgress' => in_array($this->model->pdf_generation_status, [PdfGenerationStatus::QUEUED, PdfGenerationStatus::PROCESSING], true),
            'hasPdf' => $hasPdf,
        ]);
    }
}
