<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('its_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('depot_id')->constrained()->cascadeOnDelete();

            // alim, satis, devir, eczane_satis, ihracat, uretim,
            // deaktivasyon, iptal_* ...
            $table->string('type');

            $table->json('payload');                // İTS'ye gönderilen veri
            $table->json('its_response')->nullable(); // İTS'den dönen yanıt

            $table->enum('status', ['pending', 'sent', 'failed', 'cancelled'])
                ->default('pending');
            $table->string('its_reference')->nullable(); // İTS işlem/evrak no
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('error_message')->nullable();

            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['depot_id', 'type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('its_notifications');
    }
};
