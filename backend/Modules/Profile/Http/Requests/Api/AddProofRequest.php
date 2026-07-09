<?php

namespace Modules\Profile\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class AddProofRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'nullable|string|in:assessment,endorsement,project,certificate,self',
            'label' => 'nullable|string|max:255',
            'date' => 'nullable|string|max:40',
        ];
    }
}
