<?php

namespace App\Http\Requests\Judge;

use Illuminate\Foundation\Http\FormRequest;

class CreateCompetitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // judge access enforced by middleware
    }

    public function rules(): array
    {
        $count = (int) $this->input('contestant_count', 2);

        return [
            'title'            => ['nullable', 'string', 'max:100'],
            'contestant_count' => ['required', 'integer', 'min:2', 'max:4'],
            'names'            => ['required', 'array', "min:{$count}", "max:{$count}"],
            'names.*'          => ['required', 'string', 'max:50', 'distinct'],
        ];
    }

    public function messages(): array
    {
        return [
            'names.*.distinct' => 'Contestant names must be unique.',
            'names.*'         => 'Each contestant must have a name.',
        ];
    }
}
