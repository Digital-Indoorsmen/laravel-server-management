<?php

namespace App\Http\Controllers;

use App\Jobs\InstallSoftware;
use App\Models\Server;
use App\Models\SoftwareInstallation;
use App\Services\PanelHealthService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SoftwareInstallationController extends Controller
{
    public function index(Server $server, PanelHealthService $panelHealth)
    {
        $panelHealth->systemStats($server);
        $server->refresh();

        $installedSoftware = $server->software ?? [];

        $availableSoftware = [
            [
                'id' => 'php',
                'name' => 'PHP',
                'versions' => ['7.4', '8.0', '8.1', '8.2', '8.3', '8.4', '8.5'],
                'latest' => '8.4',
                'multiVersion' => true, // Multiple versions can coexist
                'versionNotes' => [
                    '8.5' => 'Development (not recommended for production)',
                    '8.4' => 'Latest stable',
                    '8.3' => 'Stable',
                    '8.2' => 'Stable',
                    '8.1' => 'Stable',
                    '8.0' => 'Legacy',
                    '7.4' => 'Legacy (EOL Nov 2022)',
                ],
            ],
            [
                'id' => 'mariadb',
                'name' => 'MariaDB',
                'versions' => ['10.5', '10.11'],
                'latest' => '10.11',
                'versionNotes' => [
                    '10.11' => 'LTS (supported until Feb 2028)',
                    '10.5' => 'Legacy (EOL June 2025)',
                ],
            ],
            [
                'id' => 'mysql',
                'name' => 'MySQL',
                'versions' => ['8.0', '8.4'],
                'latest' => '8.4',
                'versionNotes' => [
                    '8.4' => 'Latest LTS',
                    '8.0' => 'Stable',
                ],
            ],
            [
                'id' => 'postgresql',
                'name' => 'PostgreSQL',
                'versions' => ['13', '15', '16', '17'],
                'latest' => '17',
                'versionNotes' => [
                    '17' => 'Latest stable',
                    '16' => 'Stable',
                    '15' => 'Stable',
                    '13' => 'Legacy',
                ],
            ],
        ];

        // Determine if upgrades are available for installed software
        foreach ($availableSoftware as &$item) {
            $id = $item['id'];
            $item['installed_versions'] = [];

            // Check specific versions
            if (isset($installedSoftware[$id])) {
                foreach ($installedSoftware[$id] as $version => $details) {
                    if (isset($details['status']) && $details['status'] === 'active') {
                        $item['installed_versions'][] = $version;
                    }
                }
            }

            // For PHP we can have multiple versions
            // For DB engines we usually have one
            $item['upgradeAvailable'] = false;

            if ($id !== 'php' && ! empty($item['installed_versions'])) {
                // Check if current version is less than latest
                // Just take the first one found for simplicity in DB engines
                $currentVersion = $item['installed_versions'][0];
                if (version_compare($currentVersion, $item['latest'], '<')) {
                    $item['upgradeAvailable'] = true;
                }
            }
        }

        return Inertia::render('Software/Index', [
            'server' => $server,
            'installations' => $server->softwareInstallations()->latest()->get(),
            'availableSoftware' => $availableSoftware,
            'installedSoftware' => $installedSoftware,
        ]);
    }

    public function store(Request $request, Server $server)
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:php,mariadb,mysql,postgresql'],
            'action' => ['sometimes', 'string', 'in:install,upgrade'],
            'version' => ['required_if:action,install', 'nullable', 'string'],
        ]);

        $action = $validated['action'] ?? 'install';

        $installation = SoftwareInstallation::create([
            'server_id' => $server->id,
            'type' => $validated['type'],
            'version' => $validated['version'] ?? null,
            'action' => $action,
            'status' => 'queued',
        ]);

        InstallSoftware::dispatch($installation->id);

        $verb = $action === 'upgrade' ? 'Upgrade' : 'Installation';

        return back()->with('success', "{$verb} of {$validated['type']} queued.");
    }

    public function show(SoftwareInstallation $installation)
    {
        return Inertia::render('Software/Show', [
            'installation' => $installation->load('server'),
        ]);
    }
}
