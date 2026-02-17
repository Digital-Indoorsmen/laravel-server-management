<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSiteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'domain' => ['required', 'string', 'max:255', 'unique:sites,domain'],
            'system_user' => ['required', 'string', 'max:32', 'alpha_dash', 'unique:sites,system_user'],
            'php_version' => ['required', 'string', 'in:7.4,8.1,8.2,8.3,8.4,8.5'],
            'app_type' => ['required', 'string', 'in:wordpress,laravel,generic'],
            'create_database' => ['boolean'],
            'database_type' => ['required_if:create_database,true', 'string', 'in:mariadb,postgresql'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'domain.required' => 'Please provide a domain name for the site.',
            'domain.unique' => 'A site with this domain already exists.',
            'system_user.required' => 'Please provide a system user name.',
            'system_user.unique' => 'A site with this system user already exists.',
            'system_user.alpha_dash' => 'The system user may only contain letters, numbers, dashes, and underscores.',
            'php_version.required' => 'Please select a PHP version.',
            'php_version.in' => 'The selected PHP version is not supported.',
            'app_type.required' => 'Please select an application type.',
            'database_type.required_if' => 'Please select a database type when creating a database.',
        ];
    }
}
