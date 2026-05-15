<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets_introuvables', function (Blueprint $table) {
            $table->id();
            $table->string('numero_ticket', 255);
            $table->unsignedInteger('id_usine')->nullable();
            $table->foreignId('id_utilisateur')->constrained('users')->cascadeOnDelete();
            $table->string('raison', 64)->default('not_found');
            $table->timestamps();

            $table->index('numero_ticket');
            $table->index('id_usine');
            $table->unique(['numero_ticket', 'id_utilisateur']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets_introuvables');
    }
};
