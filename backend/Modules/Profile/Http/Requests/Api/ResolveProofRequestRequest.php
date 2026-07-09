<?php

namespace Modules\Profile\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ResolveProofRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'accept' => 'required|boolean',
        ];
    }
}
