<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->string('order_number');
            $table->string('status');
            $table->date('order_date');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->timestamps();

            $table->index('organization_id');
            $table->index('status');
            $table->unique(['organization_id', 'order_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
