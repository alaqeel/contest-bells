<?php

namespace App\Http\Requests\Contestant;

use Illuminate\Foundation\Http\FormRequest;

class ClaimContestantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contestant_id' => ['required', 'integer', 'exists:contestants,id'],
        ];
    }
}
