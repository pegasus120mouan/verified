<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class TicketStoreController extends Controller
{
    /**
     * Enregistre en base locale les tickets (schéma Pegasus / unipalm_gestion_new).
     * Les déclencheurs MySQL recalculent montant_paie à l’insertion / mise à jour.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'tickets' => ['required', 'array', 'min:1', 'max:50'],
            'tickets.*' => ['required', 'array'],
            'tickets.*.numero_ticket' => ['required', 'string', 'max:255'],
        ]);

        /** @var list<array<string, mixed>> $ticketsInput */
        $ticketsInput = $request->input('tickets', []);

        $user = $request->user();
        $saved = 0;

        foreach ($ticketsInput as $index => $t) {
            if (! is_array($t)) {
                continue;
            }

            $v = Validator::make($t, [
                'id_usine' => ['required', 'integer'],
                'id_agent' => ['required', 'integer'],
                'vehicule_id' => ['required', 'integer'],
                'date_ticket' => ['required'],
            ]);

            if ($v->fails()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Données API incomplètes pour le ticket #'.((int) $index + 1).' (id_usine, id_agent, vehicule_id, date_ticket requis).',
                    'errors' => $v->errors(),
                ], 422);
            }

            $numero = trim((string) ($t['numero_ticket'] ?? ''));
            if ($numero === '') {
                continue;
            }

            if (Ticket::existsByNumero($numero)) {
                return response()->json([
                    'ok' => false,
                    'reason' => 'already_verified',
                    'message' => 'Le ticket « '.$numero.' » a déjà été vérifié.',
                ], 409);
            }

            $dateRaw = $t['date_ticket'] ?? null;
            try {
                $dateTicket = Carbon::parse($dateRaw)->startOfDay();
            } catch (\Throwable) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Date de ticket invalide pour « '.$numero.' ».',
                ], 422);
            }

            $idUtilisateur = (int) $user->id;

            $statut = $t['statut_ticket'] ?? 'non soldé';
            if (! in_array($statut, ['soldé', 'non soldé'], true)) {
                $statut = 'non soldé';
            }

            $verifiedAt = now();
            $apiCreatedAt = $this->nullableDateTime($t['created_at'] ?? null) ?? $verifiedAt;

            Ticket::withoutTimestamps(function () use ($numero, $t, $dateTicket, $idUtilisateur, $statut, $apiCreatedAt, $verifiedAt): void {
                Ticket::updateOrCreate(
                    ['numero_ticket' => $numero],
                    [
                        'id_usine' => (int) $t['id_usine'],
                        'date_ticket' => $dateTicket->toDateString(),
                        'id_agent' => (int) $t['id_agent'],
                        'vehicule_id' => (int) $t['vehicule_id'],
                        'poids' => $this->nullableFloat($t['poids'] ?? null),
                        'id_utilisateur' => $idUtilisateur,
                        'prix_unitaire' => $this->decimalOrDefault($t['prix_unitaire'] ?? 0, 2),
                        'date_validation_boss' => $this->nullableDateTime($t['date_validation_boss'] ?? null),
                        'montant_payer' => $this->nullableDecimalString($t['montant_payer'] ?? null, 2),
                        'montant_reste' => $this->nullableDecimalString($t['montant_reste'] ?? null, 2),
                        'date_paie' => $this->nullableDateTime($t['date_paie'] ?? null),
                        'statut_ticket' => $statut,
                        'numero_bordereau' => $this->nullableString($t['numero_bordereau'] ?? null, 255),
                        'created_at' => $apiCreatedAt,
                        'updated_at' => $verifiedAt,
                    ]
                );
            });
            $saved++;
        }

        return response()->json([
            'ok' => true,
            'message' => $saved === 1
                ? '1 ticket enregistré en local.'
                : $saved.' tickets enregistrés en local.',
            'saved' => $saved,
        ]);
    }

    private function nullableString(mixed $value, int $max): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $s = mb_substr(trim((string) $value), 0, $max);

        return $s === '' ? null : $s;
    }

    private function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $normalized = str_replace(',', '.', preg_replace('/\s+/u', '', (string) $value) ?? '');

        return is_numeric($normalized) ? (float) $normalized : null;
    }

    private function decimalOrDefault(mixed $value, int $fractionDigits): string
    {
        if ($value === null || $value === '') {
            return number_format(0.0, $fractionDigits, '.', '');
        }

        if (is_numeric($value)) {
            return number_format((float) $value, $fractionDigits, '.', '');
        }

        $normalized = str_replace(',', '.', preg_replace('/\s+/u', '', (string) $value) ?? '');

        return is_numeric($normalized)
            ? number_format((float) $normalized, $fractionDigits, '.', '')
            : number_format(0.0, $fractionDigits, '.', '');
    }

    private function nullableDecimalString(mixed $value, int $fractionDigits): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return number_format((float) $value, $fractionDigits, '.', '');
        }

        $normalized = str_replace(',', '.', preg_replace('/\s+/u', '', (string) $value) ?? '');

        return is_numeric($normalized)
            ? number_format((float) $normalized, $fractionDigits, '.', '')
            : null;
    }

    private function nullableDateTime(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
