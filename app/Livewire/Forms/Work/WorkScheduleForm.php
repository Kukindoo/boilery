<?php

namespace App\Livewire\Forms\Work;

use App\Models\Work;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Form;
use Throwable;

class WorkScheduleForm extends Form
{
    #[Locked]
    public Work $work;

    public ?string $scheduleDate = null;

    public ?string $scheduleTime = null;

    public function initForm(Work $work): void
    {
        $this->work = $work->refresh();
        $scheduled = $this->work->schedule_at?->copy()->timezone('Europe/Prague');
        $this->scheduleDate = $scheduled?->format('Y-m-d');
        $this->scheduleTime = $scheduled?->format('H:i');
    }

    public function rules(): array
    {
        return [
            'scheduleDate' => [
                'nullable', 'required_with:scheduleTime', 'date_format:Y-m-d',
                Rule::when($this->work->work_accepted_at !== null, [
                    'after_or_equal:' . $this->work->work_accepted_at?->format('Y-m-d'),
                ]),
            ],
            'scheduleTime' => [
                'nullable', 'required_with:scheduleDate', 'date_format:H:i',
                'after_or_equal:09:00', 'before_or_equal:19:00',
            ],
        ];
    }

    /**
     * @throws Throwable
     */
    public function submit(): void
    {
        DB::transaction(function (): void {
            $this->work = Work::query()->lockForUpdate()->findOrFail($this->work->id);
            Gate::authorize('update', $this->work);
            $this->validate();

            $this->work->update([
                'schedule_at' => filled($this->scheduleDate) && filled($this->scheduleTime)
                    ? Carbon::createFromFormat('!Y-m-d H:i', $this->scheduleDate . ' ' . $this->scheduleTime, 'Europe/Prague')->utc()
                    : null,
            ]);
        });
    }
}
