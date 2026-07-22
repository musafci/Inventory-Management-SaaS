<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->decimal('price', 10, 2)->default(0);
            $table->json('limits');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('organization_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_ends_at')->nullable();
            $table->timestamps();

            $table->unique('organization_id');
        });

        Schema::create('feature_flags', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('description')->nullable();
            $table->boolean('default_enabled')->default(false);
            $table->timestamps();
        });

        Schema::create('organization_feature_flags', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('feature_flag_id')->constrained()->cascadeOnDelete();
            $table->boolean('enabled');
            $table->timestamps();

            $table->unique(['organization_id', 'feature_flag_id']);
        });

        Schema::create('support_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('platform_admin_id')->constrained()->cascadeOnDelete();
            $table->text('note');
            $table->timestamps();
        });

        Schema::create('impersonation_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('platform_admin_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('impersonated_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason');
            $table->string('token_id')->nullable()->index();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('impersonation_logs');
        Schema::dropIfExists('support_notes');
        Schema::dropIfExists('organization_feature_flags');
        Schema::dropIfExists('feature_flags');
        Schema::dropIfExists('organization_subscriptions');
        Schema::dropIfExists('plans');
    }
};
