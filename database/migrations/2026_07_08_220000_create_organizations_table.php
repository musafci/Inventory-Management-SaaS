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
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('plan')->default('growth');
            $table->string('status')->default('trial');
            $table->timestamp('trial_ends_at')->nullable();
            $table->string('stripe_customer_id')->nullable();
            $table->timestamp('deletion_requested_at')->nullable();
            $table->timestamp('deletion_scheduled_for')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('phone')->nullable()->after('password');
            $table->string('status')->default('active')->after('phone');
            $table->timestamp('last_login_at')->nullable()->after('status');
            $table->foreignId('default_organization_id')->nullable()->after('last_login_at')->constrained('organizations')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('default_organization_id');
            $table->dropColumn(['phone', 'status', 'last_login_at']);
        });

        Schema::dropIfExists('organizations');
    }
};
