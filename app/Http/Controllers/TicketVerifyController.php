<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class TicketVerifyController extends Controller
{
    /**
     * Vérifie qu'un numéro de ticket existe dans l'API mes_tickets et, si id_usine est fourni,
     * qu'il appartient bien à cette usine.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'numero' => ['required', 'string', 'min:2', 'max:190'],
            'id_usine' => ['nullable', 'integer', 'min:1'],
        ]);

        $needle = $this->normalizeNumero($validated['numero']);
        $idUsine = isset($validated['id_usine']) ? (int) $validated['id_usine'] : null;

        $url = config('services.pegasus.mes_tickets_url');
        $maxPages = max(1, min(200, (int) config('services.pegasus.mes_tickets_max_pages', 100)));

        try {
            $page = 1;
            $lastPage = 1;

            do {
                $response = Http::timeout(30)
                    ->acceptJson()
                    ->get($url, ['page' => $page]);

                if (! $response->successful()) {
                    return response()->json([
                        'valid' => false,
                        'reason' => 'api_error',
                        'message' => 'Service tickets indisponible.',
                    ], 503);
                }

                $data = $response->json();
                $lastPage = max(1, (int) ($data['pagination']['last_page'] ?? 1));
                $tickets = is_array($data['tickets'] ?? null) ? $data['tickets'] : [];

                foreach ($tickets as $ticket) {
                    if (! $this->numeroMatches($ticket, $needle)) {
                        continue;
                    }

                    $ticketUsine = (int) ($ticket['id_usine'] ?? 0);

                    if ($idUsine !== null && $idUsine > 0 && $ticketUsine !== $idUsine) {
                        return response()->json([
                            'valid' => false,
                            'reason' => 'wrong_usine',
                            'message' => 'Ticket trouvé mais il n’appartient pas à cette usine.',
                        ]);
                    }

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

                $page++;
            } while ($page <= $lastPage && $page <= $maxPages);
        } catch (\Throwable $e) {
            return response()->json([
                'valid' => false,
                'reason' => 'exception',
                'message' => 'Erreur lors de la vérification.',
            ], 500);
        }

        return response()->json([
            'valid' => false,
            'reason' => 'not_found',
            'message' => 'Ticket introuvable.',
        ]);
    }

    private function normalizeNumero(string $value): string
    {
        $value = trim($value);

        return Str::lower(preg_replace('/\s+/u', ' ', $value) ?? $value);
    }

    /**
     * @param  array<string, mixed>  $ticket
     */
    private function numeroMatches(array $ticket, string $needle): bool
    {
        $raw = (string) ($ticket['numero_ticket'] ?? '');
        $norm = $this->normalizeNumero($raw);

        if ($norm === $needle) {
            return true;
        }

        $compactNeedle = str_replace(' ', '', $needle);
        $compactNorm = str_replace(' ', '', $norm);

        return $compactNorm !== '' && $compactNorm === $compactNeedle;
    }
}
