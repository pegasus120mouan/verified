<?php

namespace App\Http\Controllers;

use App\Services\VerifiedTicketCounts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class UsinesCatalogController extends Controller
{
    /**
     * Liste des usines (API catalog usines.php — id_usine + nom_usine).
     */
    public function index(Request $request): View
    {
        $page = max(1, (int) $request->query('page', 1));
        $search = trim((string) $request->query('search', ''));

        $url = config('services.pegasus.usines_catalog_url');
        $query = array_filter([
            'page' => $page,
            'search' => $search !== '' ? $search : null,
        ], fn ($v) => $v !== null && $v !== '');

        $usines = [];
        $pagination = null;
        $error = null;

        try {
            $response = Http::timeout(20)
                ->acceptJson()
                ->get($url, $query);

            if ($response->successful()) {
                $data = $response->json();
                $usines = is_array($data['usines'] ?? null) ? $data['usines'] : [];
                $pagination = is_array($data['pagination'] ?? null) ? $data['pagination'] : null;
            } else {
                $error = 'Impossible de charger la liste des usines (erreur HTTP '.$response->status().').';
            }
        } catch (\Throwable $e) {
            $error = 'Impossible de contacter l’API des usines.';
        }

        $usineIds = array_map(
            static fn (array $u) => (int) ($u['id_usine'] ?? 0),
            $usines
        );

        return view('usines.index', [
            'usines' => $usines,
            'pagination' => $pagination,
            'error' => $error,
            'search' => $search,
            'ticketCountsByUsine' => VerifiedTicketCounts::byUsineForUser((int) $request->user()->id, $usineIds),
        ]);
    }
}
