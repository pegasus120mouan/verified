<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PegasusMesTicketsLookup
{
    /**
     * Recherche un ticket dans l’API mes_tickets (pagination complète jusqu’à la limite configurée).
     *
     * @return array{status: 'found', ticket: array<string, mixed>}|array{status: 'not_found'}|array{status: 'wrong_usine'}|array{status: 'api_error', message: string}
     */
    public function findTicket(string $numeroSaisi, ?int $idUsine): array
    {
        $needle = $this->normalizeNumero($numeroSaisi);
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
                    return [
                        'status' => 'api_error',
                        'message' => 'Service tickets indisponible (HTTP '.$response->status().').',
                    ];
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
                        return ['status' => 'wrong_usine'];
                    }

                    return ['status' => 'found', 'ticket' => $ticket];
                }

                $page++;
            } while ($page <= $lastPage && $page <= $maxPages);
        } catch (\Throwable) {
            return ['status' => 'api_error', 'message' => 'Erreur lors de la vérification.'];
        }

        return ['status' => 'not_found'];
    }

    /**
     * Charge toutes les pages mes_tickets et indexe par numéro compact (espaces retirés, minuscules).
     * À utiliser pour les imports en masse (ex. Excel).
     *
     * @return array{ok: true, index: array<string, array<string, mixed>>}|array{ok: false, message: string}
     */
    public function fetchAllTicketsByCompactKey(): array
    {
        $index = [];
        $url = config('services.pegasus.mes_tickets_url');
        $maxPages = max(1, min(200, (int) config('services.pegasus.mes_tickets_max_pages', 100)));

        try {
            $page = 1;
            $lastPage = 1;

            do {
                $response = Http::timeout(60)
                    ->acceptJson()
                    ->get($url, ['page' => $page]);

                if (! $response->successful()) {
                    return [
                        'ok' => false,
                        'message' => 'Service tickets indisponible (HTTP '.$response->status().').',
                    ];
                }

                $data = $response->json();
                $lastPage = max(1, (int) ($data['pagination']['last_page'] ?? 1));
                $tickets = is_array($data['tickets'] ?? null) ? $data['tickets'] : [];

                foreach ($tickets as $ticket) {
                    $raw = (string) ($ticket['numero_ticket'] ?? '');
                    $needle = $this->normalizeNumero($raw);
                    $compact = str_replace(' ', '', $needle);
                    if ($compact !== '') {
                        $index[$compact] = $ticket;
                    }
                }

                $page++;
            } while ($page <= $lastPage && $page <= $maxPages);
        } catch (\Throwable) {
            return ['ok' => false, 'message' => 'Erreur lors du chargement des tickets.'];
        }

        return ['ok' => true, 'index' => $index];
    }

    /**
     * @param  array<string, array<string, mixed>>  $index
     * @return array{status: 'found', ticket: array<string, mixed>}|array{status: 'not_found'}|array{status: 'wrong_usine'}
     */
    public function findTicketInIndex(array $index, string $numeroSaisi, ?int $idUsine): array
    {
        $needle = $this->normalizeNumero($numeroSaisi);
        $compactNeedle = str_replace(' ', '', $needle);
        if ($compactNeedle === '') {
            return ['status' => 'not_found'];
        }

        $ticket = $index[$compactNeedle] ?? null;
        if ($ticket === null) {
            return ['status' => 'not_found'];
        }

        $ticketUsine = (int) ($ticket['id_usine'] ?? 0);

        if ($idUsine !== null && $idUsine > 0 && $ticketUsine !== $idUsine) {
            return ['status' => 'wrong_usine'];
        }

        return ['status' => 'found', 'ticket' => $ticket];
    }

    public function normalizeNumero(string $value): string
    {
        $value = trim($value);

        return Str::lower(preg_replace('/\s+/u', ' ', $value) ?? $value);
    }

    /**
     * @param  array<string, mixed>  $ticket
     */
    public function numeroMatches(array $ticket, string $needle): bool
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
