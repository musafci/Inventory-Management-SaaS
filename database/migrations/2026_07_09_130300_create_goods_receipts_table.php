<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->constrained()->restrictOnDelete();
            $table->foreignId('received_by')->constrained('users')->restrictOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('received_at');
            $table->timestamps();

            $table->index('organization_id');
            $table->index('purchase_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipts');
    }
};
