<?php

namespace Modules\PublicProfile\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ProofRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from' => 'required|string|max:255',
            'relation' => 'nullable|string|max:255',
            'skill' => 'required|string|max:255',
        ];
    }
}
