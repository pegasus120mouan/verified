<?php

namespace App\Services;

use App\Models\Ticket;

class VerifiedTicketCounts
{
    /**
     * Nombre de tickets validés (enregistrés localement) par id_usine pour un utilisateur.
     *
     * @param  list<int>  $usineIds
     * @return array<int, int>
     */
    public static function byUsineForUser(int $userId, array $usineIds): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map(static fn ($id) => (int) $id, $usineIds),
            static fn (int $id) => $id > 0
        )));

        if ($ids === []) {
            return [];
        }

        return Ticket::query()
            ->where('id_utilisateur', $userId)
            ->whereIn('id_usine', $ids)
            ->groupBy('id_usine')
            ->selectRaw('id_usine, COUNT(*) as nombre')
            ->pluck('nombre', 'id_usine')
            ->map(static fn ($count) => (int) $count)
            ->all();
    }
}
