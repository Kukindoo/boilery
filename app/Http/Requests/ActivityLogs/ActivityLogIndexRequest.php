<?php

namespace App\Http\Requests\ActivityLogs;

use Illuminate\Foundation\Http\FormRequest;
use Spatie\Activitylog\Models\Activity;

class ActivityLogIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Activity::class);
    }
}
