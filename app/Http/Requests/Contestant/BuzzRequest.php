<?php

namespace App\Http\Requests\Contestant;

use Illuminate\Foundation\Http\FormRequest;

class BuzzRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // contestant auth enforced by middleware
    }

    public function rules(): array
    {
        return []; // no body required — just authenticate the request
    }
}
