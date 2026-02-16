<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('servers')) {
            Schema::table('servers', function (Blueprint $table) {
                if (! Schema::hasColumn('servers', 'setup_token')) {
                    $table->string('setup_token')->nullable()->unique()->after('status');
                }
                if (! Schema::hasColumn('servers', 'setup_completed_at')) {
                    $table->timestamp('setup_completed_at')->nullable()->after('setup_token');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('servers')) {
            Schema::table('servers', function (Blueprint $table) {
                $columns = [];
                if (Schema::hasColumn('servers', 'setup_token')) {
                    $columns[] = 'setup_token';
                }
                if (Schema::hasColumn('servers', 'setup_completed_at')) {
                    $columns[] = 'setup_completed_at';
                }
                if (count($columns) > 0) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
