<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stok_kalemleri', function (Blueprint $t) {
            $t->string('lokasyon', 80)->nullable()->after('durum'); // reyon/raf; SKT'si geçenler "Red Odası"
            $t->index(['tenant_id', 'durum', 'lokasyon']);
        });
    }

    public function down(): void
    {
        Schema::table('stok_kalemleri', function (Blueprint $t) {
            $t->dropIndex(['tenant_id', 'durum', 'lokasyon']);
            $t->dropColumn('lokasyon');
        });
    }
};
