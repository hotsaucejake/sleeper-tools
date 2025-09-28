<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SelectLeagueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'league' => 'sometimes|required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'league.required' => 'League ID is required',
            'league.string' => 'League ID must be a string',
        ];
    }
}
