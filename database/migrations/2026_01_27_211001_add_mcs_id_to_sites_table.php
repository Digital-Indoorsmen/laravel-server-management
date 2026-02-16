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
        if (Schema::hasTable('sites') && ! Schema::hasColumn('sites', 'mcs_id')) {
            Schema::table('sites', function (Blueprint $table) {
                $table->integer('mcs_id')->nullable()->unique()->after('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sites') && Schema::hasColumn('sites', 'mcs_id')) {
            Schema::table('sites', function (Blueprint $table) {
                $table->dropColumn('mcs_id');
            });
        }
    }
};
