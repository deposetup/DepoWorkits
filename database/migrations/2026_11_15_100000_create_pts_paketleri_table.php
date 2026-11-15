<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pts_paketleri', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->string('transfer_id', 40);
            $t->string('kaynak_gln', 13)->nullable();
            $t->string('belge_no', 40)->nullable();
            $t->unsignedInteger('urun_sayisi')->default(0);
            $t->boolean('md5_uyumlu')->nullable();
            $t->string('durum', 16)->default('indirildi'); // indirildi|mal_alindi
            $t->longText('xml');
            $t->timestamps();
            $t->unique(['tenant_id', 'transfer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pts_paketleri');
    }
};
