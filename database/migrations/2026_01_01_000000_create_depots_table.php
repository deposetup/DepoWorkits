<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depots', function (Blueprint $table) {
            $table->id();
            $table->string('company_title');           // firma unvanı
            $table->string('authorized_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            // İTS/İEGM erişim bilgileri — sadece admin değiştirebilir,
            // müşteri panelde salt-okunur görür.
            $table->string('gln_number')->unique();
            $table->text('its_password_encrypted');

            $table->enum('status', ['active', 'passive'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depots');
    }
};
