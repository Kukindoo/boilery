<?php

namespace App\Http\Requests\SpareParts;

use Illuminate\Foundation\Http\FormRequest;

class SparePartShowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('spare_part'));
    }
}
