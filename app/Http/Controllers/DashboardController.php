<?php

namespace App\Http\Controllers;

use App\Enums\IssueStatus;
use App\Enums\QuoteStatus;
use App\Enums\Roles;
use App\Enums\WorkStatus;
use App\Models\Issue;
use App\Models\Quote;
use App\Models\Work;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $statistics = [];

        if ($request->user()->hasAnyRole(Roles::ADMIN->value, Roles::STAKEHOLDER->value)) {
            foreach ([
                ['quotes', 'Poptávky', Quote::class, QuoteStatus::cases()],
                ['works', 'Zakázky', Work::class, WorkStatus::cases()],
                ['issues', 'Případy', Issue::class, IssueStatus::cases()],
            ] as [$key, $heading, $model, $statuses]) {
                $canView = $request->user()->can('viewAny', $model);
                /** @var Model $model */
                $counts = $canView
                    ? $model::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')
                        ->toBase()
                        ->pluck('total', 'status')
                    : collect();
                $statistics[$key] = [
                    'heading' => $heading,
                    'route' => $key . '.index',
                    'canView' => $canView,
                    'total' => $canView ? (int) $counts->sum() : null,
                    'statuses' => [],
                ];

                if ($canView) {
                    /** @var array $statuses */
                    foreach ($statuses as $status) {
                        $statistics[$key]['statuses'][$status->value] = [
                            'label' => $status->label(),
                            'colour' => $status->colour(),
                            'count' => (int) $counts->get($status->value, 0),
                        ];
                    }
                }
            }
        }

        return view('dashboard', compact('statistics'));
    }
}
