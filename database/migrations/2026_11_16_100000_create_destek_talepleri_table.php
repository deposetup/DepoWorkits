<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('destek_talepleri', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('konu', 24); // its_sifre|its_kullanici|baglanti|diger
            $t->text('mesaj')->nullable();
            $t->string('telefon', 30)->nullable();
            $t->string('durum', 12)->default('yeni'); // yeni|islemde|kapandi
            $t->timestamps();
            $t->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('destek_talepleri');
    }
};
