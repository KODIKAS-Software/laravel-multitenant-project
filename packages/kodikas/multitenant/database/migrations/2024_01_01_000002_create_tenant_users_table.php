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
        Schema::create('tenant_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('user_type', [
                'owner', 'admin', 'employee', 'client',
                'vendor', 'partner', 'consultant', 'guest',
            ])->default('employee');
            $table->enum('role', [
                'super_admin', 'admin', 'manager', 'employee', 'client', 'viewer',
            ])->default('employee');
            $table->enum('status', [
                'active', 'inactive', 'suspended', 'pending', 'blocked',
            ])->default('active');
            $table->json('permissions')->nullable();
            $table->foreignId('invited_by')->nullable()->constrained('users');
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('last_access_at')->nullable();
            $table->json('access_restrictions')->nullable();
            $table->json('custom_data')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'user_id']);
            $table->index(['user_type']);
            $table->index(['role']);
            $table->index(['status']);
            $table->index(['last_access_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_users');
    }
};
