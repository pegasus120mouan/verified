<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets_introuvables', function (Blueprint $table) {
            $table->dropUnique(['numero_ticket', 'id_utilisateur']);
            $table->unique('numero_ticket');
        });
    }

    public function down(): void
    {
        Schema::table('tickets_introuvables', function (Blueprint $table) {
            $table->dropUnique(['numero_ticket']);
            $table->unique(['numero_ticket', 'id_utilisateur']);
        });
    }
};
