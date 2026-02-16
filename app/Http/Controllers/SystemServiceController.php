<?php

namespace App\Http\Controllers;

use App\Services\ServiceControlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SystemServiceController extends Controller
{
    public function __invoke(Request $request, ServiceControlService $serviceControl, string $service, string $action): RedirectResponse
    {
        $validator = Validator::make(
            ['service' => $service, 'action' => $action],
            [
                'service' => ['required', Rule::in(ServiceControlService::allowedServices())],
                'action' => ['required', Rule::in(ServiceControlService::allowedActions())],
            ]
        );

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $result = $serviceControl->control($service, $action);

        if (! $result['ok']) {
            return back()->withErrors(['service_control' => $result['message']]);
        }

        return back()->with('success', $result['message']);
    }
}
