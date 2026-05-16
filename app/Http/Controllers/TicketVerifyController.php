<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Services\PegasusMesTicketsLookup;
use App\Services\TicketIntrouvableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketVerifyController extends Controller
{
    /**
     * Vérifie qu'un numéro de ticket existe dans l'API mes_tickets et, si id_usine est fourni,
     * qu'il appartient bien à cette usine.
     */
    public function __invoke(
        Request $request,
        TicketIntrouvableService $introuvables,
        PegasusMesTicketsLookup $lookup,
    ): JsonResponse {
        $validated = $request->validate([
            'numero' => ['required', 'string', 'min:2', 'max:190'],
            'id_usine' => ['nullable', 'integer', 'min:1'],
        ]);

        $idUsine = isset($validated['id_usine']) ? (int) $validated['id_usine'] : null;
        $numeroSaisi = trim($validated['numero']);

        if (Ticket::existsByNumero($numeroSaisi)) {
            return response()->json([
                'valid' => false,
                'reason' => 'already_verified',
                'message' => 'Ce ticket a déjà été vérifié.',
            ]);
        }

        if ($introuvables->existsInDatabase($numeroSaisi)) {
            return response()->json([
                'valid' => false,
                'reason' => 'already_reported_introuvable',
                'message' => $introuvables->messageForExisting($numeroSaisi),
            ]);
        }

        $result = $lookup->findTicket($numeroSaisi, $idUsine);

        if ($result['status'] === 'api_error') {
            return response()->json([
                'valid' => false,
                'reason' => 'api_error',
                'message' => $result['message'] ?? 'Service tickets indisponible.',
            ], 503);
        }

        if ($result['status'] === 'wrong_usine') {
            return response()->json([
                'valid' => false,
                'reason' => 'wrong_usine',
                'message' => 'Ticket trouvé mais il n’appartient pas à cette usine.',
            ]);
        }

        if ($result['status'] === 'found') {
            $ticket = $result['ticket'];

            if (Ticket::existsByNumero((string) ($ticket['numero_ticket'] ?? ''))) {
                return response()->json([
                    'valid' => false,
                    'reason' => 'already_verified',
                    'message' => 'Ce ticket a déjà été vérifié.',
                ]);
            }

            return response()->json([
                'valid' => true,
                'ticket' => $ticket,
            ]);
        }

        $created = $introuvables->record($numeroSaisi, $idUsine, $request->user());

        return response()->json([
            'valid' => false,
            'reason' => 'not_found',
            'recorded' => true,
            'recorded_new' => $created,
            'message' => $created
                ? 'Ticket introuvable. Enregistré en base locale pour suivi.'
                : 'Ticket introuvable (déjà enregistré en base locale).',
        ]);
    }
}
