<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Services\PegasusReferenceLookup;
use App\Services\VerifiedTicketCounts;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class VerificationController extends Controller
{
    public function index(Request $request): View
    {
        $page = max(1, (int) $request->query('page', 1));
        $search = trim((string) $request->query('search', ''));

        $url = config('services.pegasus.mes_usines_url');
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

        return view('verifications', [
            'usines' => $usines,
            'pagination' => $pagination,
            'error' => $error,
            'search' => $search,
            'ticketCountsByUsine' => VerifiedTicketCounts::byUsineForUser((int) $request->user()->id, $usineIds),
        ]);
    }

    public function show(Request $request, string $id_usine, PegasusReferenceLookup $lookup): View
    {
        [$usine, $error] = $this->fetchUsineFromApi($id_usine);

        if ($error !== null) {
            return view('verification-usine', [
                'usine' => null,
                'error' => $error,
                'tickets' => null,
                'agentNames' => [],
            ]);
        }

        if ($usine === null) {
            throw new NotFoundHttpException;
        }

        $idUsine = (int) $id_usine;

        $tickets = Ticket::query()
            ->where('id_usine', $idUsine)
            ->where('id_utilisateur', $request->user()->id)
            ->orderByDesc('date_ticket')
            ->orderByDesc('id_ticket')
            ->paginate(20)
            ->withQueryString();

        $agentNames = $lookup->agentsById();

        return view('verification-usine', [
            'usine' => $usine,
            'error' => null,
            'tickets' => $tickets,
            'agentNames' => $agentNames,
        ]);
    }

    public function printPointTonnage(Request $request, string $id_usine, PegasusReferenceLookup $lookup): Response|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date'],
        ]);

        $validator->after(function ($validator) use ($request): void {
            if ($request->filled('date_debut') && $request->filled('date_fin')) {
                $debut = Carbon::parse($request->date('date_debut'))->startOfDay();
                $fin = Carbon::parse($request->date('date_fin'))->startOfDay();
                if ($fin->lt($debut)) {
                    $validator->errors()->add('date_fin', 'La date fin doit être après ou égale à la date début.');
                }
            }
        });

        if ($validator->fails()) {
            return redirect()
                ->route('verifications.usine', ['id_usine' => $id_usine])
                ->withErrors($validator)
                ->withInput()
                ->with('open_point_tonnage_modal', true);
        }

        [$usine, $apiError] = $this->fetchUsineFromApi($id_usine);

        if ($apiError !== null) {
            return redirect()
                ->route('verifications.usine', ['id_usine' => $id_usine])
                ->with('error', $apiError);
        }

        if ($usine === null) {
            throw new NotFoundHttpException;
        }

        $idUsine = (int) $id_usine;

        $query = Ticket::query()
            ->where('id_usine', $idUsine)
            ->where('id_utilisateur', $request->user()->id)
            ->orderByDesc('date_ticket')
            ->orderByDesc('id_ticket');

        if ($request->filled('date_debut')) {
            $query->where('created_at', '>=', Carbon::parse($request->date('date_debut'))->startOfDay());
        }

        if ($request->filled('date_fin')) {
            $query->where('created_at', '<=', Carbon::parse($request->date('date_fin'))->endOfDay());
        }

        $tickets = $query->get();
        $agentNames = $lookup->agentsById();

        $nomUsine = $usine['nom_usine'] ?? ($lookup->usinesById()[$idUsine] ?? ('Usine #'.$idUsine));

        $totalPoidsKg = (float) $tickets->sum(fn (Ticket $t) => (float) ($t->poids ?? 0));

        $filterDateDebut = $request->filled('date_debut')
            ? Carbon::parse($request->date('date_debut'))->format('d/m/Y')
            : null;
        $filterDateFin = $request->filled('date_fin')
            ? Carbon::parse($request->date('date_fin'))->format('d/m/Y')
            : null;

        return Pdf::loadView('verifications.point_tonnage_pdf', [
            'tickets' => $tickets,
            'agentNames' => $agentNames,
            'nomUsine' => $nomUsine,
            'idUsine' => $idUsine,
            'filterDateDebut' => $filterDateDebut,
            'filterDateFin' => $filterDateFin,
            'totalPoidsKg' => $totalPoidsKg,
            'verifierName' => $request->user()->name ?: $request->user()->email,
        ])
            ->setPaper('a4', 'portrait')
            ->stream('point-tonnage-usine-'.$idUsine.'.pdf');
    }

    /**
     * @return array{0: ?array<string, mixed>, 1: ?string} [usine, error]
     */
    private function fetchUsineFromApi(string $id_usine): array
    {
        $url = config('services.pegasus.mes_usines_url');
        $maxPages = 100;

        try {
            for ($page = 1; $page <= $maxPages; $page++) {
                $response = Http::timeout(20)
                    ->acceptJson()
                    ->get($url, ['page' => $page]);

                if (! $response->successful()) {
                    return [null, 'Impossible de charger les informations de l’usine.'];
                }

                $data = $response->json();
                $list = is_array($data['usines'] ?? null) ? $data['usines'] : [];
                $lastPage = max(1, (int) ($data['pagination']['last_page'] ?? 1));

                foreach ($list as $u) {
                    if ((string) ($u['id_usine'] ?? '') === (string) $id_usine) {
                        return [$u, null];
                    }
                }

                if ($page >= $lastPage) {
                    break;
                }
            }
        } catch (\Throwable $e) {
            return [null, 'Impossible de contacter l’API des usines.'];
        }

        return [null, null];
    }
}
