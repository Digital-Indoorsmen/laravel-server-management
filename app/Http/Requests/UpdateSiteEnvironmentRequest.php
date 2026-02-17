<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSiteEnvironmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => ['sometimes', 'nullable', 'string'],
            'auto_cache_config' => ['sometimes', 'boolean'],
            'auto_restart_queue' => ['sometimes', 'boolean'],
        ];
    }
}
