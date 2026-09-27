<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('its_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('depot_id')->constrained()->cascadeOnDelete();
            $table->date('stock_date');                 // stoğun ait olduğu gün (KDS: çekildiği günün bir öncesi)
            $table->string('gtin', 14);
            $table->string('product_name')->nullable();
            $table->unsignedInteger('its_quantity');    // İTS'deki stok adedi
            $table->timestamps();

            $table->unique(['depot_id', 'stock_date', 'gtin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('its_stocks');
    }
};
