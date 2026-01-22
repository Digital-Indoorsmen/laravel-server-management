# Status: [x] Complete
# Title: SQLite Optimization & Database Configuration

## Description
Configure and optimize the SQLite database used for the panel management system to ensure high performance and reliability. This includes enabling WAL mode and setting performance-oriented pragmas.

## Requirements
- Update `config/database.php` to include a `pragmas` array for the `sqlite` connection.
- Enable WAL (Write-Ahead Logging) mode.
- Configure performance pragmas:
  - `journal_mode = WAL`
  - `synchronous = NORMAL`
  - `cache_size = -20000` (20 MB)
  - `foreign_keys = ON`
  - `busy_timeout = 5000`
  - `temp_store = MEMORY`
- Implement a bootstrap mechanism (e.g., in `App\Providers\AppServiceProvider`) to ensure these pragmas are applied to the connection.

## Implementation Details
### Example `config/database.php` update:
```php
'sqlite' => [
    'driver' => 'sqlite',
    'url' => env('DB_URL'),
    'database' => env('DB_DATABASE', database_path('database.sqlite')),
    'prefix' => '',
    'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
],
```

### Example `AppServiceProvider::boot()` implementation:
```php
public function boot(): void
{
    if (config('database.default') === 'sqlite') {
        $db = DB::connection()->getPdo();
        $db->exec('PRAGMA journal_mode = WAL;');
        $db->exec('PRAGMA synchronous = NORMAL;');
        $db->exec('PRAGMA cache_size = -20000;');
        $db->exec('PRAGMA foreign_keys = ON;');
        $db->exec('PRAGMA busy_timeout = 5000;');
        $db->exec('PRAGMA temp_store = MEMORY;');
    }
}
```

## Configuration
- SQLite 3
- Laravel 12 Database Configuration

## Audit & Logging
- Monitor `database.sqlite` file size periodically.
- Log database integrity checks during application boot or via a scheduled task.

## Testing
- Verify WAL mode is active: `PRAGMA journal_mode;` should return `wal`.
- Benchmark read/write performance under concurrent load using `ab` or similar tool.

## Completion Criteria
- [x] SQLite optimized with all requested pragmas
- [x] Laravel `config/database.php` reviewed
- [x] `AppServiceProvider` applies pragmas on connection
- [x] Verification command or test case confirms WAL mode is enabled
