<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cariler', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('gln', 13);
            $t->string('unvan');
            $t->string('tip', 12)->nullable(); // uretici|depo|eczane|hastane|ihracatci
            $t->string('il', 60)->nullable();
            $t->string('ilce', 60)->nullable();
            $t->string('eposta')->nullable();
            $t->string('telefon', 30)->nullable();
            $t->boolean('aktif')->default(true); // paydaşın eklendiği andaki İTS durumu
            $t->timestamps();
            $t->unique(['tenant_id', 'gln']);
            $t->index(['tenant_id', 'unvan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cariler');
    }
};
