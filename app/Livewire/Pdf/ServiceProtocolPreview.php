<?php

namespace App\Livewire\Pdf;

use App\Actions\Works\PrepareServiceProtocolData;
use App\Models\Work;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

class ServiceProtocolPreview extends Component
{
    #[Locked]
    public Work $work;

    public function mount(Work $work): void
    {
        Gate::authorize('view', $work);

        $this->work = $work;
    }

    #[On('assembly-action-added')]
    #[On('assembly-actions-changed')]
    #[On('spare-part-added')]
    #[On('spare-parts-changed')]
    public function refreshPreview(): void
    {
        Gate::authorize('view', $this->work);

        $this->work->refresh();
    }

    /**
     * @throws Throwable
     */
    public function render(): View
    {
        Gate::authorize('view', $this->work);

        $pdfContent = view('pdfs.service-protocol-pdf', app(PrepareServiceProtocolData::class)->handle($this->work)->toArray())->render();

        return view('livewire.pdf.service-protocol-preview', compact('pdfContent'));
    }
}
