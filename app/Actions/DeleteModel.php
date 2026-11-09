<?php

namespace App\Actions;

use App\Actions\AssemblyActions\DeleteAssemblyAction;
use App\Actions\Quotes\DeleteQuote;
use App\Actions\SpareParts\DeleteSparePart;
use App\Actions\Works\DeleteWork;
use App\Models\AssemblyAction;
use App\Models\Quote;
use App\Models\SparePart;
use App\Models\Work;
use App\Traits\LogsActionActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

class DeleteModel
{
    use LogsActionActivity;

    /** @throws Throwable */
    public function handle(Model $model): void
    {
        if ($model instanceof Quote) {
            app(DeleteQuote::class)->handle($model);

            return;
        }
        if ($model instanceof Work) {
            app(DeleteWork::class)->handle($model);

            return;
        }
        if ($model instanceof SparePart) {
            app(DeleteSparePart::class)->handle($model);

            return;
        }
        if ($model instanceof AssemblyAction) {
            app(DeleteAssemblyAction::class)->handle($model);

            return;
        }

        try {
            DB::transaction(function () use ($model): void {
                $model->refreshForUpdate();
                Gate::authorize('delete', $model);

                $model->delete();
                $this->logActionCompleted($model->getTable(), $model, class_basename($model) . ' deleted');
            }, attempts: 5);
        } catch (Throwable $exception) {
            $this->logActionAttempt($model->getTable(), $model, $exception);

            throw $exception;
        }
    }
}
