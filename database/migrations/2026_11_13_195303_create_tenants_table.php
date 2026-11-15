<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $t) {
            $t->id();
            $t->string('unvan');
            $t->string('gln', 13)->unique();
            $t->string('tur')->default('eczane'); // eczane, hastane, depo, uretici, ihracatci
            $t->string('il')->nullable();
            $t->string('ilce')->nullable();
            $t->string('eposta')->nullable();
            $t->string('telefon')->nullable();
            $t->date('lisans_baslangic');
            $t->date('lisans_bitis');
            $t->boolean('aktif')->default(true);
            $t->boolean('bitince_salt_okunur')->default(true);
            $t->timestamp('kvkk_onay_tarihi')->nullable();
            $t->timestamps();
        });

        Schema::table('users', function (Blueprint $t) {
            $t->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $t->string('rol')->default('tenant_user'); // super_admin, tenant_admin, tenant_user
            $t->boolean('aktif')->default(true);
            $t->timestamp('son_giris')->nullable();
            $t->string('son_giris_ip', 45)->nullable();
            $t->unsignedTinyInteger('hatali_giris')->default(0);
            $t->timestamp('kilit_bitis')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropConstrainedForeignId('tenant_id');
            $t->dropColumn(['rol', 'aktif', 'son_giris', 'son_giris_ip', 'hatali_giris', 'kilit_bitis']);
        });
        Schema::dropIfExists('tenants');
    }
};
