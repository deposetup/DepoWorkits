<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Yönetim ekranından değiştirilebilen sistem ayarları (anahtar => değer). Mail ayarları burada tutulur.
        Schema::create('ayarlar', function (Blueprint $t) {
            $t->string('anahtar', 100)->primary();
            $t->text('deger')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ayarlar');
    }
};
