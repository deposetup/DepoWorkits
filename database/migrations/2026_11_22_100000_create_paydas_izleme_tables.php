<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Son bilinen İTS paydaş listesi (karşılaştırma için).
        Schema::create('paydas_anliklari', function (Blueprint $t) {
            $t->id();
            $t->string('tip', 12);
            $t->string('gln', 13);
            $t->string('unvan');
            $t->boolean('aktif')->default(true);
            $t->string('il', 60)->nullable();
            $t->string('ilce', 60)->nullable();
            $t->timestamps();
            $t->unique(['tip', 'gln']);
        });

        // Tespit edilen değişiklikler: yeni, pasif, aktif, kaldirildi.
        Schema::create('paydas_degisimleri', function (Blueprint $t) {
            $t->id();
            $t->string('tip', 12);
            $t->string('gln', 13);
            $t->string('unvan');
            $t->string('olay', 12);
            $t->string('il', 60)->nullable();
            $t->timestamp('bildirildi_at')->nullable(); // e-posta ile iletildiğinde dolar
            $t->timestamp('created_at')->nullable();
            $t->index(['tip', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paydas_degisimleri');
        Schema::dropIfExists('paydas_anliklari');
    }
};
