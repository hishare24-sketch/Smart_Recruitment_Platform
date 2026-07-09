<?php

namespace Modules\PublicProfile\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class RateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stars' => 'required|integer|min:1|max:5',
        ];
    }
}
