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
        if (! Schema::hasTable('database_engine_installations')) {
            Schema::create('database_engine_installations', function (Blueprint $table) {
                $table->ulid('id')->primary();
                $table->foreignUlid('server_id')->constrained()->cascadeOnDelete();
                $table->string('type'); // mariadb, mysql, postgresql
                $table->string('status'); // queued, installing, active, error
                $table->longText('log')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('database_engine_installations');
    }
};
