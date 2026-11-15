<?php

use App\Support\Metin;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // E-posta artık zorunlu değil (GLN + şifre ile de girilebilir).
        Schema::table('users', function (Blueprint $t) {
            $t->string('email')->nullable()->change();
        });

        Schema::create('giris_kayitlari', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $t->string('kimlik', 255)->nullable(); // yazılan e-posta ya da GLN (şifre asla saklanmaz)
            $t->string('yontem', 8); // eposta|gln
            $t->boolean('basarili');
            $t->string('ip', 45)->nullable();
            $t->string('tarayici', 255)->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['tenant_id', 'created_at']);
            $t->index('created_at');
        });

        // Mevcut tüm müşteri ünvanlarını standarda (Her Kelimenin Baş Harfi Büyük) getir.
        foreach (DB::table('tenants')->select('id', 'unvan')->get() as $t) {
            $yeni = Metin::baslik($t->unvan);
            if ($yeni !== $t->unvan) {
                DB::table('tenants')->where('id', $t->id)->update(['unvan' => $yeni]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('giris_kayitlari');
    }
};
