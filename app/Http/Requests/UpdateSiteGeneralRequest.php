<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSiteGeneralRequest extends FormRequest
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
            'app_type' => ['sometimes', 'string', 'in:wordpress,laravel,generic'],
            'php_version' => ['sometimes', 'string', 'in:7.4,8.1,8.2,8.3,8.4,8.5'],
            'tags' => ['sometimes', 'nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'color' => ['sometimes', 'nullable', 'string', 'max:20'],
            'document_root' => ['sometimes', 'string', 'max:255'],
            'git_repository' => ['sometimes', 'nullable', 'string', 'max:255'],
            'git_branch' => ['sometimes', 'string', 'max:100'],
        ];
    }
}
