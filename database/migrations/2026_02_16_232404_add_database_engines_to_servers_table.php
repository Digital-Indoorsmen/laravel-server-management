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
        if (Schema::hasTable('servers') && ! Schema::hasColumn('servers', 'database_engines')) {
            Schema::table('servers', function (Blueprint $table) {
                $table->json('database_engines')->nullable()->after('web_server');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('servers', 'database_engines')) {
            Schema::table('servers', function (Blueprint $table) {
                $table->dropColumn('database_engines');
            });
        }
    }
};
