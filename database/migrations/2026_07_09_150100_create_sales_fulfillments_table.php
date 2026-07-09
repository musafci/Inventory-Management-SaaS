<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_fulfillments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_order_id')->constrained()->restrictOnDelete();
            $table->foreignId('fulfilled_by')->constrained('users')->restrictOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('fulfilled_at');
            $table->timestamps();

            $table->index('organization_id');
            $table->index('sales_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_fulfillments');
    }
};
