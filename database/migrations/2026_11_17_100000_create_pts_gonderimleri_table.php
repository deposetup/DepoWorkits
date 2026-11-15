<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pts_gonderimleri', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('alici_gln', 13);
            $t->string('bildirim_id', 40)->nullable(); // ilgili İTS Satış bildirimi
            $t->unsignedInteger('adet')->default(0);
            $t->json('kalemler');
            $t->string('durum', 16)->default('bekliyor'); // bekliyor|gonderiliyor|gonderildi|hata|vazgecildi
            $t->string('transfer_id', 40)->nullable();
            $t->string('md5', 40)->nullable();
            $t->text('hata')->nullable();
            $t->timestamp('gonderildi_at')->nullable();
            $t->timestamps();
            $t->index(['tenant_id', 'durum']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pts_gonderimleri');
    }
};
