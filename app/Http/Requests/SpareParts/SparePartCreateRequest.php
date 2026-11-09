<?php

namespace App\Http\Requests\SpareParts;

use App\Models\SparePart;
use Illuminate\Foundation\Http\FormRequest;

class SparePartCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', SparePart::class);
    }
}
