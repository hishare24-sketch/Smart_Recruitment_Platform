<?php

namespace Modules\PublicProfile\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePublicProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'doc' => 'sometimes|array',
        ];
    }
}
