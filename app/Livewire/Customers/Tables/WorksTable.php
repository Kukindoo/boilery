<?php

namespace App\Livewire\Customers\Tables;

use App\Models\Issue;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class WorksTable extends Component
{
    use WithPagination;

    #[Locked]
    public Issue $issue;

    public string $heading = 'Zakázky zákazníka';

    public bool $paginate = true;

    public function render(): View
    {
        Gate::authorize('view', $this->issue);

        $query = $this->issue->works()->latest('id');

        return view('livewire.customers.tables.works-table', [
            'works' => $this->paginate
                ? $query->paginate(10, pageName: 'works_page')
                : $query->get(),
        ]);
    }
}
