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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('domain')->nullable()->unique();
            $table->string('subdomain')->nullable()->unique();
            $table->string('database_name')->nullable();
            $table->string('database_host')->nullable();
            $table->integer('database_port')->nullable();
            $table->string('database_username')->nullable();
            $table->string('database_password')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended', 'trial'])->default('active');
            $table->string('plan')->default('basic');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();
            $table->json('settings')->nullable();
            $table->json('limits')->nullable();
            $table->json('custom_data')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status']);
            $table->index(['plan']);
            $table->index(['trial_ends_at']);
            $table->index(['subscription_ends_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
