<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSiteRequest;
use App\Models\Server;
use App\Models\Site;
use App\Services\SiteProvisioningService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SiteController extends Controller
{
    public function __construct(
        protected SiteProvisioningService $provisioner
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Server $server)
    {
        return Inertia::render('Sites/Index', [
            'server' => $server,
            'sites' => $server->sites()->latest()->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Server $server)
    {
        return Inertia::render('Sites/Create', [
            'server' => $server,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSiteRequest $request, Server $server)
    {
        $validated = $request->validated();

        // Default document root based on app type
        $documentRoot = "/home/{$validated['system_user']}/public_html";
        if ($validated['app_type'] === 'laravel') {
            $documentRoot .= '/public';
        }

        DB::beginTransaction();

        try {
            $site = $server->sites()->create([
                'domain' => $validated['domain'],
                'system_user' => $validated['system_user'],
                'php_version' => $validated['php_version'],
                'app_type' => $validated['app_type'],
                'web_server' => $validated['web_server'] ?? null,
                'document_root' => $documentRoot,
                'status' => 'creating',
            ]);

            if ($request->boolean('create_database')) {
                // Generate secure DB name and credentials
                // Name: db_{slugged_domain_without_dashes}
                $cleanName = str_replace(['.', '-'], '_', $validated['domain']);
                $dbName = 'db_'.substr($cleanName, 0, 50); // Ensure length is reasonable

                // User: same as system user but prefixed or just same?
                // Requirement: "Create a dedicated database user".
                // Let's use system_user as the base, it's unique per server usually.
                // But mysql users have 32 char limit (MariaDB < 10.x used to be 16, but we are on modern).
                $dbUser = $validated['system_user'];

                $site->databases()->create([
                    'server_id' => $server->id,
                    'name' => $dbName,
                    'username' => $dbUser,
                    'password' => Str::password(24),
                    'type' => $validated['database_type'],
                    'status' => 'creating',
                ]);
            }

            // Dispatch provisioning (synchronous for now for simplicity, should be queued)
            $this->provisioner->provision($site);

            DB::commit();

            return redirect()->route('servers.sites.index', $server)
                ->with('success', 'Site created and provisioned successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Failed to create site: '.$e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Site $site)
    {
        return Inertia::render('Sites/Show', [
            'site' => $site->load('server'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Site $site)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Site $site)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Site $site)
    {
        // TODO: Implement deprovisioning logic
        $site->delete();

        return redirect()->route('servers.sites.index', $site->server)
            ->with('success', 'Site deleted.');
    }

    public function env(Site $site)
    {
        $content = $this->provisioner->getEnvContent($site);

        return Inertia::render('Sites/Env', [
            'site' => $site->load('server'),
            'content' => $content,
        ]);
    }

    public function updateEnv(Request $request, Site $site)
    {
        $validated = $request->validate([
            'content' => ['required', 'string'],
        ]);

        try {
            $this->provisioner->updateEnvContent($site, $validated['content']);

            return back()->with('success', 'Environment updated successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update environment: '.$e->getMessage()]);
        }
    }
}
