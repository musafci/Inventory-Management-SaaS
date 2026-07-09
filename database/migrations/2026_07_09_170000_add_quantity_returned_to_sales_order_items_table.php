<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_order_items', function (Blueprint $table) {
            $table->unsignedInteger('quantity_returned')->default(0)->after('quantity_fulfilled');
        });
    }

    public function down(): void
    {
        Schema::table('sales_order_items', function (Blueprint $table) {
            $table->dropColumn('quantity_returned');
        });
    }
};
