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
            $table->decimal('price_monthly', 10, 2)->nullable();
            $table->decimal('price_annual', 10, 2)->nullable();
            $table->json('limits');
            $table->boolean('is_custom')->default(false);
            $table->unsignedTinyInteger('grace_buffer_percent')->default(10);
            $table->unsignedSmallInteger('sort_order')->default(0);
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
            $table->string('stripe_subscription_id')->nullable();
            $table->string('billing_interval')->nullable();
            $table->timestamp('past_due_at')->nullable();
            $table->timestamp('trial_reminder_sent_at')->nullable();
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

        Schema::create('stripe_events', function (Blueprint $table): void {
            $table->id();
            $table->string('event_id')->unique();
            $table->string('type');
            $table->timestamps();
        });

        Schema::create('organization_data_exports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->string('file_path')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_data_exports');
        Schema::dropIfExists('stripe_events');
        Schema::dropIfExists('impersonation_logs');
        Schema::dropIfExists('support_notes');
        Schema::dropIfExists('organization_feature_flags');
        Schema::dropIfExists('feature_flags');
        Schema::dropIfExists('organization_subscriptions');
        Schema::dropIfExists('plans');
    }
};
