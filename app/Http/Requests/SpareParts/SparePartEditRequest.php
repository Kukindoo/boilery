<?php

namespace App\Http\Requests\SpareParts;

use Illuminate\Foundation\Http\FormRequest;

class SparePartEditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('spare_part'));
    }
}
