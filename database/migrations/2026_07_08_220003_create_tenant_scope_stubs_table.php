<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Minimal tenant-scoped table used only to prove the global scope
     * fails closed. Replaced by real domain tables in later phases.
     */
    public function up(): void
    {
        Schema::create('tenant_scope_stubs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->timestamps();

            $table->index('organization_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_scope_stubs');
    }
};
