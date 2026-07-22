<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->string('description')->nullable()->after('guard_name');
            $table->boolean('is_protected')->default(false)->after('description');
            $table->boolean('is_system')->default(false)->after('is_protected');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->dropColumn(['description', 'is_protected', 'is_system']);
        });
    }
};
