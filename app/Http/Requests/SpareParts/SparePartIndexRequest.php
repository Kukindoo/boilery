<?php

namespace App\Http\Requests\SpareParts;

use App\Models\SparePart;
use Illuminate\Foundation\Http\FormRequest;

class SparePartIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', SparePart::class);
    }
}
