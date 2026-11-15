<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pts_gonderimleri', function (Blueprint $t) {
            $t->unsignedBigInteger('kaynak_id')->nullable()->after('user_id'); // yeniden oluşturulan gönderimin kaynağı
            $t->string('belge_no', 30)->nullable()->after('bildirim_id');
            $t->string('alici_unvan', 255)->nullable()->after('alici_gln');
        });
    }

    public function down(): void
    {
        Schema::table('pts_gonderimleri', function (Blueprint $t) {
            $t->dropColumn(['kaynak_id', 'belge_no', 'alici_unvan']);
        });
    }
};
