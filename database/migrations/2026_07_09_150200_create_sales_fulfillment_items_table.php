<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_fulfillment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_fulfillment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_order_item_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity_fulfilled');
            $table->timestamps();

            $table->unique(['sales_fulfillment_id', 'sales_order_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_fulfillment_items');
    }
};
