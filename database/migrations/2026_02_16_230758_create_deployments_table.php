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
        Schema::create('deployments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('site_id')->constrained('sites')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('triggered_via', 32)->default('ui');
            $table->string('status', 24)->index();
            $table->string('branch', 255)->default('main');
            $table->string('commit_hash', 64)->nullable();
            $table->longText('stdout')->nullable();
            $table->longText('stderr')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deployments');
    }
};
