<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

it('optimizes sqlite with correct pragmas', function () {
    // Setup a temp sqlite database to test WAL
    $tempDbPath = tempnam(sys_get_temp_dir(), 'test_db_');
    unlink($tempDbPath);
    $tempDbPath .= '.sqlite';
    touch($tempDbPath);

    Config::set('database.connections.test_sqlite', array_merge(
        config('database.connections.sqlite'),
        ['database' => $tempDbPath]
    ));

    $connection = DB::connection('test_sqlite');
    $pdo = $connection->getPdo();

    // Re-apply pragmas as AppServiceProvider might have applied them to the default connection
    $pragmas = config('database.connections.sqlite.pragmas', []);
    foreach ($pragmas as $key => $value) {
        $pdo->exec("PRAGMA {$key} = {$value};");
    }

    $journalMode = $connection->selectOne('PRAGMA journal_mode')->journal_mode;
    $synchronous = $connection->selectOne('PRAGMA synchronous')->synchronous;
    $foreignKeys = $connection->selectOne('PRAGMA foreign_keys')->foreign_keys;
    $busyTimeout = $connection->selectOne('PRAGMA busy_timeout')->timeout;
    $cacheSize = $connection->selectOne('PRAGMA cache_size')->cache_size;

    unlink($tempDbPath);

    expect($journalMode)->toBe('wal')
        ->and($synchronous)->toBe(1) // 1 = NORMAL
        ->and($foreignKeys)->toBe(1) // 1 = ON
        ->and($busyTimeout)->toBe(5000)
        ->and($cacheSize)->toBe(-20000);
});
