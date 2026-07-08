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
        Schema::table('users', function (Blueprint $table) {
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
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_organization_id');
            $table->dropColumn(['phone', 'status', 'last_login_at']);
        });
    }
};
