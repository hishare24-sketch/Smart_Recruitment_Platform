<?php

namespace Modules\PublicProfile\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'visitorName' => 'nullable|string|max:255',
            'date' => 'nullable|string|max:40',
            'slot' => 'nullable|string|max:40',
            'note' => 'nullable|string|max:2000',
        ];
    }
}
