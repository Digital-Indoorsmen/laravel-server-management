# Status: [x] Complete
# Title: Site Settings and Safe Reconfiguration

## Parent
- Depends on: `029a-site-workspace-foundation.md`

## Goal
Replace the placeholder `Settings.vue` and empty `SiteController.update()` with a fully functional, multi-section settings page modeled after Laravel Forge. The page uses a left-sidebar navigation with 6 sub-sections.

## Current State
- `Settings.vue` (`resources/js/Pages/Sites/Workspace/Settings.vue`) — placeholder card, no forms.
- `SiteController.update()` — empty method body.
- `SiteWorkspaceController.settings()` — renders the shell via `renderTab()` with no extra props.
- `Env.vue` (`resources/js/Pages/Sites/Env.vue`) — standalone page at `/sites/{site}/env`, not integrated into settings.
- `sites` table columns: `id`, `server_id`, `domain`, `document_root`, `system_user`, `php_version`, `app_type`, `web_server`, `status`, `mcs_id`.
- Missing: deploy script, git repo/branch, notes, tags, various toggles.

## Reference (Forge Screenshots)
The Forge settings page has a left sidebar with 6 sub-sections:
1. **General** — Framework/app type, PHP version, tags, avatar/color, notes, root/web directories, Git settings.
2. **Deployments** — Push-to-deploy toggle, deploy script code editor, deploy hook URL, health checks, GitHub deployments toggle.
3. **Environment** — `.env` editor (blurred by default with Reveal button), auto config:cache toggle, auto queue:restart toggle.
4. **Composer** — Manage `auth.json` credentials for private packages.
5. **Notifications** — (empty state for now).
6. **Integrations** — (empty state for now).

---

## Implementation Plan

### 1. Migration — Add settings columns to `sites` table

**File:** `[NEW] database/migrations/YYYY_MM_DD_HHMMSS_add_settings_columns_to_sites_table.php`

Create via `php artisan make:migration add_settings_columns_to_sites_table --table=sites`.

Use idempotent pattern (matching existing migrations like `add_mcs_id_to_sites_table.php`):

```php
// Only add if column doesn't already exist
if (Schema::hasTable('sites') && !Schema::hasColumn('sites', 'deploy_script')) {
    Schema::table('sites', function (Blueprint $table) {
        // General settings
        $table->json('tags')->nullable()->after('status');
        $table->text('notes')->nullable()->after('tags');
        $table->string('color')->nullable()->after('notes');
        $table->string('git_repository')->nullable()->after('color');
        $table->string('git_branch')->default('main')->after('git_repository');

        // Deployment settings
        $table->text('deploy_script')->nullable()->after('git_branch');
        $table->boolean('push_to_deploy')->default(false)->after('deploy_script');
        $table->string('deploy_hook_url')->nullable()->unique()->after('push_to_deploy');
        $table->boolean('health_check_enabled')->default(false)->after('deploy_hook_url');
        $table->boolean('github_deployments_enabled')->default(false)->after('health_check_enabled');
        $table->boolean('env_in_deploy_script')->default(false)->after('github_deployments_enabled');

        // Environment settings
        $table->boolean('auto_cache_config')->default(true)->after('env_in_deploy_script');
        $table->boolean('auto_restart_queue')->default(true)->after('auto_cache_config');
    });
}
```

**Site model updates** (`app/Models/Site.php`):
- Add `casts()` method:
  ```php
  protected function casts(): array
  {
      return [
          'tags' => 'array',
          'push_to_deploy' => 'boolean',
          'health_check_enabled' => 'boolean',
          'github_deployments_enabled' => 'boolean',
          'env_in_deploy_script' => 'boolean',
          'auto_cache_config' => 'boolean',
          'auto_restart_queue' => 'boolean',
      ];
  }
  ```

---

### 2. Controller — `SiteSettingsController`

**File:** `[NEW] app/Http/Controllers/SiteSettingsController.php`

Create via `php artisan make:controller SiteSettingsController`.

Dedicated controller (not overloading `SiteController`) with section-based update methods:

```php
class SiteSettingsController extends Controller
{
    public function __construct(
        protected SiteProvisioningService $provisioner
    ) {}

    // GET /sites/{site}/workspace/settings/{section?}
    public function show(Site $site, string $section = 'general'): Response
    {
        $validSections = ['general', 'deployments', 'environment', 'composer', 'notifications', 'integrations'];
        if (!in_array($section, $validSections)) {
            $section = 'general';
        }

        $data = [
            'site' => $site->load('server'),
            'workspace' => [
                'activeTab' => 'settings',
                'title' => 'Settings',
                'description' => 'Site metadata and safe reconfiguration defaults.',
            ],
            'activeSection' => $section,
            'phpVersions' => ['7.4', '8.1', '8.2', '8.3', '8.4', '8.5'],
            'appTypes' => ['wordpress', 'laravel', 'generic'],
        ];

        // Only load env content when environment section is active
        if ($section === 'environment') {
            $data['envContent'] = Inertia::lazy(fn () => $this->provisioner->getEnvContent($site));
        }

        return Inertia::render('Sites/Workspace/Settings', $data);
    }

    // PATCH /sites/{site}/workspace/settings/general
    public function updateGeneral(UpdateSiteGeneralRequest $request, Site $site): RedirectResponse
    {
        $site->update($request->validated());
        return back()->with('success', 'General settings updated.');
    }

    // PATCH /sites/{site}/workspace/settings/deployments
    public function updateDeployments(UpdateSiteDeploymentsRequest $request, Site $site): RedirectResponse
    {
        $site->update($request->validated());
        return back()->with('success', 'Deployment settings updated.');
    }

    // PATCH /sites/{site}/workspace/settings/environment
    public function updateEnvironment(UpdateSiteEnvironmentRequest $request, Site $site): RedirectResponse
    {
        if ($request->has('content')) {
            $this->provisioner->updateEnvContent($site, $request->validated('content'));
        }

        $site->update($request->safe()->only(['auto_cache_config', 'auto_restart_queue']));
        return back()->with('success', 'Environment settings updated.');
    }
}
```

---

### 3. Form Requests

**File:** `[NEW] app/Http/Requests/UpdateSiteGeneralRequest.php`
```php
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
```

**File:** `[NEW] app/Http/Requests/UpdateSiteDeploymentsRequest.php`
```php
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
```

**File:** `[NEW] app/Http/Requests/UpdateSiteEnvironmentRequest.php`
```php
public function rules(): array
{
    return [
        'content' => ['sometimes', 'nullable', 'string'],
        'auto_cache_config' => ['sometimes', 'boolean'],
        'auto_restart_queue' => ['sometimes', 'boolean'],
    ];
}
```

---

### 4. Routes

**File:** `[MODIFY] routes/web.php`

Replace the current settings route inside the workspace group:
```diff
-Route::get('/settings', [SiteWorkspaceController::class, 'settings'])->name('settings');
+Route::get('/settings/{section?}', [SiteSettingsController::class, 'show'])->name('settings');
+Route::patch('/settings/general', [SiteSettingsController::class, 'updateGeneral'])->name('settings.general.update');
+Route::patch('/settings/deployments', [SiteSettingsController::class, 'updateDeployments'])->name('settings.deployments.update');
+Route::patch('/settings/environment', [SiteSettingsController::class, 'updateEnvironment'])->name('settings.environment.update');
```

Keep the existing standalone env routes (`/sites/{site}/env`) for backward compat but consider deprecating.

---

### 5. Frontend — Settings Page

**File:** `[MODIFY] resources/js/Pages/Sites/Workspace/Settings.vue`

Full rewrite. Structure:

```
┌─────────────────────────────────────────────────────┐
│ SiteWorkspaceLayout (shared tabs)                   │
│ ┌──────────┬────────────────────────────────────────┐│
│ │ Sidebar  │  Content Panel                        ││
│ │          │                                        ││
│ │ General  │  (renders active section component)    ││
│ │ Deploy.. │                                        ││
│ │ Environ. │                                        ││
│ │ Composer │                                        ││
│ │ Notific. │                                        ││
│ │ Integra. │                                        ││
│ └──────────┴────────────────────────────────────────┘│
└─────────────────────────────────────────────────────┘
```

Props: `site`, `workspace`, `activeSection`, `phpVersions`, `appTypes`, `envContent` (lazy).

The sidebar uses `<Link>` navigation with `route('sites.workspace.settings', [site.id, sectionKey])`. The content area conditionally renders sub-section components based on `activeSection`.

**Sub-section components (all `[NEW]`):**

#### `resources/js/Components/Sites/Settings/GeneralSettings.vue`
- **Settings** section header with description
- **Framework** — `<select>` with app types (with icon, e.g. Laravel logo)
- **PHP version** — `<select>` with PHP versions
- **Tags** — text input (comma-separated or chip input)
- **Notes** — textarea with "Add note" button
- **Directories** section:
  - **Root directory** — read-only display of `/home/{system_user}/` with editable suffix
  - **Web directory** — read-only display with editable suffix (e.g. `/public`)
- **Git** section:
  - **Repository** — text input
  - **Branch** — text input
- Each section group has its own save button using `useForm` + `form.patch()`

#### `resources/js/Components/Sites/Settings/DeploymentSettings.vue`
- **Push to deploy** — toggle switch
- **Deploy script** — monospace `<textarea>` (code editor style, dark bg) showing bash script
- **"Make .env variables available to deploy script"** — checkbox + info text
- **"Looking for zero downtime deployments?"** — info link
- **Deploy Hook** — read-only URL field with copy + regenerate buttons
- **Health checks** — toggle switch
- **GitHub deployments** — toggle switch
- Uses `useForm` for each setting group

#### `resources/js/Components/Sites/Settings/EnvironmentSettings.vue`
- **Environment variables** — blurred textarea by default, "Reveal" button to unblur
- Warning: "Environment variables should not be shared publicly"
- **Cache** toggle — "Run `php artisan config:cache` after updating environment variables"
- **Queues** toggle — "Run `php artisan queue:restart` after adding environment variables"
- Uses `useForm` for env content and toggles separately

#### `resources/js/Components/Sites/Settings/ComposerSettings.vue`
- Description about managing `auth.json`
- Empty state: "No Composer credentials yet"
- "+ Add credential" button (disabled/non-functional, future task)

#### `resources/js/Components/Sites/Settings/NotificationSettings.vue`
- Empty state card: "Notification settings coming soon."

#### `resources/js/Components/Sites/Settings/IntegrationSettings.vue`
- Empty state card: "Integration settings coming soon."

---

### 6. Remove settings method from `SiteWorkspaceController`

**File:** `[MODIFY] app/Http/Controllers/SiteWorkspaceController.php`

Remove the `settings()` method — it's replaced by `SiteSettingsController.show()`.

---

### 7. Default deploy script generation

When a site is created (in `SiteController.store()`), auto-generate a default `deploy_script` based on `app_type`:

- **Laravel**: standard Forge-style deploy script (cd, git pull, composer install, artisan migrate, etc.)
- **WordPress**: simpler pull + cache clear
- **Generic**: minimal cd + git pull

Also auto-generate `deploy_hook_url` using `Str::random(64)` or a signed URL approach.

**File:** `[MODIFY] app/Http/Controllers/SiteController.php` (in `store()` method)

---

## Deliverables Checklist
- [x] Migration adding settings columns to `sites` table
- [x] `Site` model updated with `casts()` method
- [x] `SiteSettingsController` with `show`, `updateGeneral`, `updateDeployments`, `updateEnvironment`
- [x] 3 Form Request classes for validation
- [x] Routes wired in `routes/web.php`
- [x] `Settings.vue` rewritten with left-sidebar and sub-section routing
- [x] `GeneralSettings.vue` component
- [x] `DeploymentSettings.vue` component
- [x] `EnvironmentSettings.vue` component
- [x] `ComposerSettings.vue` component (empty state)
- [x] `NotificationSettings.vue` component (empty state)
- [x] `IntegrationSettings.vue` component (empty state)
- [x] `SiteWorkspaceController.settings()` removed
- [x] Default deploy script + hook URL auto-generated on site creation
- [x] Confirmation modal for high-risk changes (PHP version, app type)

---

## Tests

### Existing tests that must continue passing:
- `php artisan test --compact tests/Feature/SiteWorkspaceRoutingTest.php` — tests all 8 workspace tabs render for auth users and redirect guests. **This test will need updating** to account for the new controller handling the settings route.
- `php artisan test --compact tests/Feature/SiteDeploymentsTest.php`
- `php artisan test --compact tests/Feature/SiteProvisioningTest.php`

### New tests to create:

**File:** `[NEW] tests/Feature/SiteSettingsTest.php`

Create via `php artisan make:test SiteSettingsTest --pest`.

```
it('renders the general settings section by default')
it('renders each settings section for authenticated users') // dataset
it('redirects guests from settings to login')
it('updates general settings successfully')
it('validates general settings input') // invalid php_version, app_type, etc.
it('updates deployment settings successfully')
it('validates deployment settings input')
it('updates environment settings successfully')
it('validates environment settings input')
it('falls back to general section for invalid section parameter')
```

Run with: `php artisan test --compact tests/Feature/SiteSettingsTest.php`

### Full suite verification:
After all changes: `php artisan test --compact`

---

## Completion Criteria
- [x] Site settings page has Forge-style left sidebar with 6 sub-sections
- [x] General, Deployments, and Environment sub-sections are fully functional
- [x] Composer, Notifications, and Integrations sub-sections render empty states
- [x] All settings are editable with robust validation
- [x] High-risk changes (PHP version, app type) show confirmation modal
- [x] Existing env page functionality preserved (moved into settings environment section)
- [x] Default deploy script auto-generated per app type
- [x] All tests pass (existing + new)
