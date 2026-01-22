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
        Schema::create('servers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('ip_address')->unique();
            $table->string('hostname');
            $table->string('os_version'); // alma_8, alma_9, rocky_8, rocky_9
            $table->foreignUlid('ssh_key_id')->nullable()->constrained('ssh_keys')->nullOnDelete();
            $table->string('status'); // provisioning, active, error
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};
