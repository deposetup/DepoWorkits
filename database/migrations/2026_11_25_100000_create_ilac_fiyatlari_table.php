<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ilac_fiyatlari', function (Blueprint $t) {
            $t->string('gtin', 14)->primary();
            $t->string('ad', 500)->nullable()->index();
            $t->string('uretici', 500)->nullable();
            $t->boolean('aktif')->default(true);
            $t->boolean('ithal')->default(false);
            $t->date('gecerlilik_tarihi')->nullable();
            $t->date('degisiklik_tarihi')->nullable();
            $t->decimal('isf', 14, 2)->nullable();
            $t->decimal('dsf', 14, 2)->nullable();
            $t->decimal('psf_kdvsiz', 14, 2)->nullable();
            $t->decimal('psf', 14, 2)->nullable();
            $t->decimal('kurum_isk', 8, 2)->nullable();
            $t->decimal('ozel_isk', 8, 2)->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ilac_fiyatlari');
    }
};
