<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketIntrouvable extends Model
{
    protected $table = 'tickets_introuvables';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'numero_ticket',
        'id_usine',
        'id_utilisateur',
        'raison',
    ];

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_utilisateur', 'id');
    }

    public static function existsByNumero(string $numero, int $userId): bool
    {
        return static::findByNumero($numero, $userId) !== null;
    }

    public static function findByNumero(string $numero, int $userId): ?self
    {
        $trimmed = trim($numero);
        if ($trimmed === '') {
            return null;
        }

        $existing = static::query()
            ->where('id_utilisateur', $userId)
            ->where('numero_ticket', $trimmed)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $compact = strtolower(str_replace(' ', '', preg_replace('/\s+/u', '', $trimmed) ?? ''));
        if ($compact === '') {
            return null;
        }

        return static::query()
            ->where('id_utilisateur', $userId)
            ->whereRaw('LOWER(REPLACE(TRIM(numero_ticket), " ", "")) = ?', [$compact])
            ->first();
    }

    /**
     * Enregistre un ticket introuvable (API). Retourne true si nouvel enregistrement.
     */
    public static function record(string $numero, ?int $idUsine, int $userId, string $raison = 'not_found'): bool
    {
        $numero = trim($numero);
        if ($numero === '') {
            return false;
        }

        $existing = static::findByNumero($numero, $userId);

        if ($existing !== null) {
            $existing->update([
                'id_usine' => $idUsine ?? $existing->id_usine,
                'raison' => $raison,
            ]);

            return false;
        }

        static::create([
            'numero_ticket' => $numero,
            'id_usine' => $idUsine,
            'id_utilisateur' => $userId,
            'raison' => $raison,
        ]);

        return true;
    }
}
