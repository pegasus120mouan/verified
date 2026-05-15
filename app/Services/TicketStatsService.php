<?php

namespace App\Services;

use App\Models\Ticket;

class TicketStatsService
{
    /**
     * @return array{total: int, non_soldes: int, soldes: int}
     */
    public function forUser(int $userId): array
    {
        $row = Ticket::query()
            ->where('id_utilisateur', $userId)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN statut_ticket = 'non soldé' THEN 1 ELSE 0 END) as non_soldes")
            ->selectRaw("SUM(CASE WHEN statut_ticket = 'soldé' THEN 1 ELSE 0 END) as soldes")
            ->first();

        return [
            'total' => (int) ($row->total ?? 0),
            'non_soldes' => (int) ($row->non_soldes ?? 0),
            'soldes' => (int) ($row->soldes ?? 0),
        ];
    }
}
