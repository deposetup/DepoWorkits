<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            // admin: sistemi ve GLN/şifre bilgilerini yönetir
            // customer: sadece kendi deposunun bilgilerini görüntüler
            $table->enum('role', ['admin', 'customer'])->default('customer');
            $table->foreignId('depot_id')->nullable()->constrained('depots')->nullOnDelete();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
