<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('its_credentials', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();
            $t->text('kullanici_adi'); // şifreli
            $t->text('sifre');         // şifreli
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('its_credentials');
    }
};
