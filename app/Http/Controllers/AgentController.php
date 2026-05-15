<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class AgentController extends Controller
{
    public function index(Request $request): View
    {
        $page = max(1, (int) $request->query('page', 1));
        $search = trim((string) $request->query('search', ''));
        $idChef = max(0, (int) $request->query('id_chef', 0));

        $url = config('services.pegasus.agents_url');
        $query = array_filter([
            'page' => $page,
            'search' => $search !== '' ? $search : null,
            'id_chef' => $idChef > 0 ? $idChef : null,
        ], fn ($v) => $v !== null && $v !== '');

        $agents = [];
        $pagination = null;
        $error = null;

        try {
            $response = Http::timeout(25)
                ->acceptJson()
                ->get($url, $query);

            if ($response->successful()) {
                $data = $response->json();
                $agents = is_array($data['agents'] ?? null) ? $data['agents'] : [];
                $pagination = is_array($data['pagination'] ?? null) ? $data['pagination'] : null;
            } else {
                $error = 'Impossible de charger la liste des agents (erreur HTTP '.$response->status().').';
            }
        } catch (\Throwable $e) {
            $error = 'Impossible de contacter l’API des agents.';
        }

        $currentPage = (int) ($pagination['current_page'] ?? $page);
        $lastPage = (int) ($pagination['last_page'] ?? 1);
        $totalAgents = (int) ($pagination['total'] ?? count($agents));

        return view('agents.index', [
            'agents' => $agents,
            'pagination' => $pagination,
            'error' => $error,
            'search' => $search,
            'idChef' => $idChef,
            'agentStats' => [
                'total' => $totalAgents,
                'page_count' => count($agents),
                'current_page' => $currentPage,
                'last_page' => max(1, $lastPage),
            ],
        ]);
    }
}
