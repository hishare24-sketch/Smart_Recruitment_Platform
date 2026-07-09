<?php

namespace Modules\Profile\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'headline' => 'sometimes|nullable|string|max:255',
            'summary' => 'sometimes|nullable|string',
            'skills' => 'sometimes|array',
            'experiences' => 'sometimes|array',
            'certificates' => 'sometimes|array',
            'prefs' => 'sometimes|array',
        ];
    }
}
