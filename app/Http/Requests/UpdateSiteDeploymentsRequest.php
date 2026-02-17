<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSiteDeploymentsRequest extends FormRequest
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
            'deploy_script' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'push_to_deploy' => ['sometimes', 'boolean'],
            'health_check_enabled' => ['sometimes', 'boolean'],
            'github_deployments_enabled' => ['sometimes', 'boolean'],
            'env_in_deploy_script' => ['sometimes', 'boolean'],
        ];
    }
}
