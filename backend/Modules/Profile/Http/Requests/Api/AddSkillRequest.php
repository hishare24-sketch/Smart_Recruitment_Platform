<?php

namespace Modules\Profile\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class AddSkillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'selfLevel' => 'required|integer|min:1|max:5',
            'category' => 'nullable|string|max:255',
        ];
    }
}
