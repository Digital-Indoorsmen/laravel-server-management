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
        if (Schema::hasTable('servers') && ! Schema::hasColumn('servers', 'web_server')) {
            Schema::table('servers', function (Blueprint $table) {
                $table->string('web_server')->default('nginx')->after('status');
            });
        }

        if (Schema::hasTable('sites') && ! Schema::hasColumn('sites', 'web_server')) {
            Schema::table('sites', function (Blueprint $table) {
                $table->string('web_server')->nullable()->after('app_type');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sites') && Schema::hasColumn('sites', 'web_server')) {
            Schema::table('sites', function (Blueprint $table) {
                $table->dropColumn('web_server');
            });
        }

        if (Schema::hasTable('servers') && Schema::hasColumn('servers', 'web_server')) {
            Schema::table('servers', function (Blueprint $table) {
                $table->dropColumn('web_server');
            });
        }
    }
};
