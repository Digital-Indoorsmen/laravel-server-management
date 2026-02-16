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
        if (Schema::hasTable('database_engine_installations') && ! Schema::hasColumn('database_engine_installations', 'version')) {
            Schema::table('database_engine_installations', function (Blueprint $table) {
                $table->string('version')->nullable()->after('action');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('database_engine_installations', 'version')) {
            Schema::table('database_engine_installations', function (Blueprint $table) {
                $table->dropColumn('version');
            });
        }
    }
};
