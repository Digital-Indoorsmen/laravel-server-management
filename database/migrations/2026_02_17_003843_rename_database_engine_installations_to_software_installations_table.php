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
        if (Schema::hasTable('database_engine_installations')) {
            Schema::rename('database_engine_installations', 'software_installations');
        }

        if (Schema::hasTable('servers') && ! Schema::hasColumn('servers', 'software')) {
            Schema::table('servers', function (Blueprint $table) {
                $table->json('software')->nullable()->after('database_engines');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('servers') && Schema::hasColumn('servers', 'software')) {
            Schema::table('servers', function (Blueprint $table) {
                $table->dropColumn('software');
            });
        }

        if (Schema::hasTable('software_installations')) {
            Schema::rename('software_installations', 'database_engine_installations');
        }
    }
};
