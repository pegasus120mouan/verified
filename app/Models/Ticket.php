<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    protected $primaryKey = 'id_ticket';

    public $incrementing = true;

    protected $keyType = 'int';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id_usine',
        'date_ticket',
        'id_agent',
        'numero_ticket',
        'vehicule_id',
        'poids',
        'id_utilisateur',
        'prix_unitaire',
        'date_validation_boss',
        'montant_paie',
        'montant_payer',
        'montant_reste',
        'date_paie',
        'statut_ticket',
        'numero_bordereau',
        'created_at',
        'updated_at',
    ];

    public function getRouteKeyName(): string
    {
        return 'id_ticket';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_ticket' => 'date',
            'date_validation_boss' => 'datetime',
            'date_paie' => 'datetime',
            'poids' => 'float',
            'prix_unitaire' => 'decimal:2',
            'montant_paie' => 'decimal:2',
            'montant_payer' => 'decimal:2',
            'montant_reste' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_utilisateur', 'id');
    }

    /**
     * Un ticket est considéré comme déjà vérifié s'il existe en base locale (même numéro, espaces ignorés, insensible à la casse).
     */
    public static function existsByNumero(string $numero): bool
    {
        $trimmed = trim($numero);
        if ($trimmed === '') {
            return false;
        }

        if (static::query()->where('numero_ticket', $trimmed)->exists()) {
            return true;
        }

        $compact = strtolower(str_replace(' ', '', preg_replace('/\s+/u', '', $trimmed) ?? ''));
        if ($compact === '') {
            return false;
        }

        return static::query()
            ->whereRaw('LOWER(REPLACE(TRIM(numero_ticket), " ", "")) = ?', [$compact])
            ->exists();
    }
}
