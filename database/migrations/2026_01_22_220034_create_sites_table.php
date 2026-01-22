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
        Schema::create('sites', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('server_id')->constrained('servers')->cascadeOnDelete();
            $table->string('domain')->unique();
            $table->string('document_root');
            $table->string('system_user');
            $table->string('php_version');
            $table->string('app_type'); // wordpress, laravel, generic
            $table->string('status'); // creating, active, suspended
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
