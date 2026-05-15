<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Si l’ancienne table Laravel (colonne user_id) est encore présente, on la remplace
 * par le schéma Pegasus (sans recréer si la table est déjà au bon format).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tickets')) {
            return;
        }

        if (! Schema::hasColumn('tickets', 'user_id')) {
            return;
        }

        DB::unprepared('DROP TRIGGER IF EXISTS update_montant_reste_et_statut');
        DB::unprepared('DROP TRIGGER IF EXISTS update_montant_paie_on_update');
        DB::unprepared('DROP TRIGGER IF EXISTS update_montant_paie');
        Schema::drop('tickets');

        Schema::create('tickets', function (Blueprint $table) {
            $table->increments('id_ticket');
            $table->integer('id_usine');
            $table->date('date_ticket');
            $table->integer('id_agent');
            $table->string('numero_ticket', 255);
            $table->integer('vehicule_id');
            $table->float('poids')->nullable();
            $table->integer('id_utilisateur');
            $table->decimal('prix_unitaire', 10, 2)->default(0);
            $table->dateTime('date_validation_boss')->nullable();
            $table->decimal('montant_paie', 20, 2)->nullable();
            $table->decimal('montant_payer', 20, 2)->nullable();
            $table->decimal('montant_reste', 20, 2)->nullable();
            $table->dateTime('date_paie')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at', 1)->nullable();
            $table->enum('statut_ticket', ['soldé', 'non soldé'])->default('non soldé');
            $table->string('numero_bordereau', 255)->nullable();

            $table->unique('numero_ticket');
            $table->index('id_usine', 'fk_id_usine');
            $table->index('id_agent', 'fk_id_agent');
            $table->index('vehicule_id', 'fk_id_vehicule');
            $table->index('id_utilisateur', 'fk_id_utilisateur');
        });

        DB::unprepared(<<<'SQL'
CREATE TRIGGER update_montant_paie BEFORE INSERT ON tickets FOR EACH ROW
BEGIN
    IF NEW.prix_unitaire IS NOT NULL AND NEW.poids IS NOT NULL THEN
        SET NEW.montant_paie = NEW.prix_unitaire * NEW.poids;
    ELSE
        SET NEW.montant_paie = 0;
    END IF;
END
SQL);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER update_montant_paie_on_update BEFORE UPDATE ON tickets FOR EACH ROW
BEGIN
    IF NEW.prix_unitaire IS NOT NULL AND NEW.poids IS NOT NULL THEN
        SET NEW.montant_paie = NEW.prix_unitaire * NEW.poids;
    ELSE
        SET NEW.montant_paie = 0;
    END IF;
END
SQL);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER update_montant_reste_et_statut BEFORE UPDATE ON tickets FOR EACH ROW
BEGIN
    IF NEW.montant_payer != OLD.montant_payer THEN
        SET NEW.montant_reste = OLD.montant_reste - (NEW.montant_payer - OLD.montant_payer);
        IF NEW.montant_reste = 0 THEN
            SET NEW.statut_ticket = 'soldé';
        ELSE
            SET NEW.statut_ticket = 'non soldé';
        END IF;
    END IF;
END
SQL);
    }

    public function down(): void
    {
        //
    }
};
