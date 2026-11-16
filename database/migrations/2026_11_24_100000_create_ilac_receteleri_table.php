<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ilac_receteleri', function (Blueprint $t) {
            $t->string('gtin', 14)->primary();
            $t->string('ad', 500)->nullable();
            $t->string('recete_tipi', 20)->nullable()->index();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ilac_receteleri');
    }
};
