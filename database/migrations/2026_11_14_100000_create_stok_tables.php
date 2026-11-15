<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Her satır tek bir ürün birimi (GTIN + seri no). Stok = durumu "stokta" olanlar.
        Schema::create('stok_kalemleri', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->string('gtin', 14);
            $t->string('sn', 20);
            $t->string('bn', 20)->nullable();
            $t->date('skt')->nullable();
            $t->string('durum', 12)->default('stokta'); // stokta|sarf|iade|deaktive
            $t->timestamp('son_islem_at')->nullable();
            $t->timestamps();
            $t->unique(['tenant_id', 'gtin', 'sn']);
            $t->index(['tenant_id', 'durum', 'gtin']);
        });

        Schema::create('hareketler', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('tur', 16); // kabul|sarf|sarf_iptal|iade|deaktive
            $t->string('gtin', 14);
            $t->string('sn', 20)->nullable();
            $t->string('bn', 20)->nullable();
            $t->date('skt')->nullable();
            $t->string('bildirim_id')->nullable();
            $t->boolean('basarili')->default(true);
            $t->string('hata')->nullable();
            $t->string('aciklama')->nullable();
            $t->timestamps();
            $t->index(['tenant_id', 'tur', 'created_at']);
        });

        Schema::create('ilac_ayarlari', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->string('gtin', 14);
            $t->unsignedInteger('kritik_stok')->default(0);
            $t->unsignedInteger('miad_gun')->default(90);
            $t->timestamps();
            $t->unique(['tenant_id', 'gtin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ilac_ayarlari');
        Schema::dropIfExists('hareketler');
        Schema::dropIfExists('stok_kalemleri');
    }
};
