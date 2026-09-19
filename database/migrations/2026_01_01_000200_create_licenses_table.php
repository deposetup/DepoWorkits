<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('depot_id')->constrained()->cascadeOnDelete();
            $table->date('starts_at');
            $table->date('ends_at');
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['ends_at', 'status']);
        });

        // Hangi hatırlatma eşiklerinin (gün) hangi lisans için gönderildiğini
        // tekrar mail atmamak için işaretler.
        Schema::create('license_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('days_before');
            $table->timestamp('sent_at');
            $table->unique(['license_id', 'days_before']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('license_reminders');
        Schema::dropIfExists('licenses');
    }
};
