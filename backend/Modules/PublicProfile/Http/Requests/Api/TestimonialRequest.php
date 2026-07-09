<?php

namespace Modules\PublicProfile\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class TestimonialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'author' => 'required|string|max:255',
            'authorRole' => 'nullable|string|max:255',
            'excerpt' => 'required|string|max:2000',
        ];
    }
}
