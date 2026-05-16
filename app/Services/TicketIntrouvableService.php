<?php

namespace App\Services;

use App\Models\TicketIntrouvable;
use App\Models\User;

class TicketIntrouvableService
{
    public function existsInDatabase(string $numero): bool
    {
        return TicketIntrouvable::findByNumero($numero) !== null;
    }

    /**
     * Enregistre le ticket introuvable en base locale. Retourne true si nouvel enregistrement.
     */
    public function record(string $numero, ?int $idUsine, User $user, string $raison = 'not_found'): bool
    {
        return TicketIntrouvable::record($numero, $idUsine, (int) $user->id, $raison);
    }

    public function messageForExisting(string $numero): string
    {
        $record = TicketIntrouvable::findByNumero($numero);

        if ($record === null) {
            return 'Ce numéro a déjà été signalé comme introuvable.';
        }

        $record->loadMissing('utilisateur');
        $par = $record->utilisateur?->name ?: $record->utilisateur?->login ?: 'un vérificateur';
        $date = $record->created_at?->format('d/m/Y H:i') ?? '';

        return 'Ce numéro a déjà été signalé comme introuvable'
            .($date !== '' ? ' le '.$date : '')
            .' par '.$par.'.';
    }
}
