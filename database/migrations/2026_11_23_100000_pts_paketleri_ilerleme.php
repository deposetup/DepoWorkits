<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pts_paketleri', function (Blueprint $t) {
            $t->text('ilerleme')->nullable(); // Mal Alım ilerleme durumu (JSON)
        });
    }

    public function down(): void
    {
        Schema::table('pts_paketleri', function (Blueprint $t) {
            $t->dropColumn('ilerleme');
        });
    }
};
