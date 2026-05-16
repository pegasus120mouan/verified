<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Services\ExcelTicketNumbersExtractor;
use App\Services\PegasusMesTicketsLookup;
use App\Services\PegasusReferenceLookup;
use App\Services\TicketIntrouvableService;
use App\Services\VerifiedTicketCounts;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class VerificationController extends Controller
{
    public function index(Request $request): View
    {
        $data = $this->mesUsinesListContext($request);

        return view('verifications', $data);
    }

    /**
     * Liste des usines pour le flux « Vérification paie » (même source API que les vérifications).
     */
    public function paie(Request $request): View
    {
        $data = $this->mesUsinesListContext($request);

        return view('verification-paie', $data);
    }

    public function paieUsine(Request $request, string $id_usine): View
    {
        [$usine, $error] = $this->fetchUsineFromApi($id_usine);

        if ($usine === null && $error === null) {
            throw new NotFoundHttpException;
        }

        // Récupérer les résultats depuis le cache si disponible
        $results = [];
        $summary = null;
        $cacheKey = session('paie_excel_cache_key');
        if ($cacheKey) {
            $cached = cache()->get($cacheKey);
            if ($cached) {
                $results = $cached['results'] ?? [];
                $summary = $cached['summary'] ?? null;
            }
        }

        return view('verification-paie-usine', [
            'usine' => $usine,
            'error' => $error,
            'id_usine' => (int) $id_usine,
            'results' => $results,
            'summary' => $summary,
        ]);
    }

    public function paieUsineVerifyExcel(
        Request $request,
        string $id_usine,
        ExcelTicketNumbersExtractor $extractor,
        PegasusMesTicketsLookup $lookup,
        TicketIntrouvableService $introuvables,
    ): RedirectResponse {
        $request->validate([
            'excel_file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
        ], [
            'excel_file.required' => 'Veuillez choisir un fichier Excel ou CSV avant de lancer la vérification.',
            'excel_file.file' => 'Le fichier envoyé est invalide.',
            'excel_file.mimes' => 'Formats acceptés : .xlsx, .xls, .csv.',
            'excel_file.max' => 'Le fichier ne doit pas dépasser 10 Mo.',
        ]);

        $idUsine = (int) $id_usine;

        $fetch = $lookup->fetchAllTicketsByCompactKey();
        if (! $fetch['ok']) {
            return redirect()
                ->route('verification-paie.usine', ['id_usine' => $id_usine])
                ->with('flash_error', $fetch['message']);
        }

        $index = $fetch['index'];

        try {
            $path = $request->file('excel_file')->getRealPath();
            if ($path === false) {
                throw new \RuntimeException('Fichier invalide.');
            }
            $numbers = $extractor->extract($path);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'ZipArchive') || str_contains($msg, 'zip extension')) {
                $msg = 'Impossible d’ouvrir le fichier Excel (.xlsx) : l’extension PHP « zip » n’est pas activée sur ce serveur. '
                    .'Dans php.ini, activez extension=zip puis redémarrez le serveur web, '
                    .'ou enregistrez votre classeur en CSV dans Excel (Fichier → Enregistrer sous → CSV UTF-8) et importez ce fichier.';
            } else {
                $msg = 'Impossible de lire le fichier : '.$msg;
            }

            return redirect()
                ->route('verification-paie.usine', ['id_usine' => $id_usine])
                ->with('flash_error', $msg);
        }

        if (count($numbers) === 0) {
            return redirect()
                ->route('verification-paie.usine', ['id_usine' => $id_usine])
                ->with('flash_error', 'Aucun numéro de ticket trouvé. Ajoutez une colonne NUMERO_TICKET ou saisissez les numéros en colonne A. Formats acceptés : .xlsx, .xls, .csv.');
        }

        $results = [];
        $summary = [
            'total' => count($numbers),
            'trouve_api' => 0,
            'deja_local' => 0,
            'mauvaise_usine' => 0,
            'introuvable' => 0,
        ];

        foreach ($numbers as $numero) {
            if (Ticket::existsByNumero($numero)) {
                $results[] = [
                    'numero' => $numero,
                    'statut' => 'deja_local',
                    'message' => 'Déjà enregistré en base locale (ticket vérifié).',
                ];
                $summary['deja_local']++;

                continue;
            }

            $r = $lookup->findTicketInIndex($index, $numero, $idUsine);

            if ($r['status'] === 'found') {
                $ticket = $r['ticket'];
                $results[] = [
                    'numero' => $numero,
                    'statut' => 'trouve_api',
                    'message' => "Présent dans l'API Pegasus pour cette usine.",
                    'date_ticket' => $ticket['date_ticket'] ?? null,
                    'poids' => $ticket['poids'] ?? null,
                    'created_at' => $ticket['created_at'] ?? null,
                ];
                $summary['trouve_api']++;
            } elseif ($r['status'] === 'wrong_usine') {
                $results[] = [
                    'numero' => $numero,
                    'statut' => 'mauvaise_usine',
                    'message' => 'Trouvé dans l’API mais rattaché à une autre usine.',
                ];
                $summary['mauvaise_usine']++;
            } else {
                $introuvables->record($numero, $idUsine, $request->user());
                $results[] = [
                    'numero' => $numero,
                    'statut' => 'introuvable',
                    'message' => 'Absent de l’API — enregistré en base locale (tickets introuvables).',
                ];
                $summary['introuvable']++;
            }
        }

        // Stocker en cache pour éviter les limites de session (expire après 30 minutes)
        $cacheKey = 'paie_excel_' . $request->user()->id . '_' . $id_usine;
        cache()->put($cacheKey, ['results' => $results, 'summary' => $summary], now()->addMinutes(30));

        return redirect()
            ->route('verification-paie.usine', ['id_usine' => $id_usine])
            ->with('paie_excel_cache_key', $cacheKey);
    }

    public function paieExcelTemplate(): StreamedResponse
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'NUMERO_TICKET');
        $sheet->setCellValue('A2', 'EX-000001');
        $sheet->setCellValue('A3', 'AY-300762');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer): void {
            $writer->save('php://output');
        }, 'modele-verification-paie.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function printIntrouvables(Request $request, string $id_usine): Response|RedirectResponse
    {
        $request->validate([
            'numeros' => ['required', 'array', 'min:1'],
            'numeros.*' => ['required', 'string'],
        ]);

        [$usine, $error] = $this->fetchUsineFromApi($id_usine);

        if ($error !== null) {
            return redirect()
                ->route('verification-paie.usine', ['id_usine' => $id_usine])
                ->with('flash_error', $error);
        }

        $numeros = $request->input('numeros', []);
        $nomUsine = $usine['nom_usine'] ?? ('Usine #' . $id_usine);

        return Pdf::loadView('verification-paie.introuvables_pdf', [
            'numeros' => $numeros,
            'nomUsine' => $nomUsine,
            'idUsine' => (int) $id_usine,
            'generatedAt' => Carbon::now()->format('d/m/Y H:i'),
            'userName' => $request->user()->name ?: $request->user()->email,
        ])
            ->setPaper('a4', 'portrait')
            ->stream('tickets-introuvables-usine-' . $id_usine . '.pdf');
    }

    public function printTrouves(Request $request, string $id_usine): Response|RedirectResponse
    {
        // Récupérer les données depuis le cache
        $cacheKey = 'paie_excel_' . $request->user()->id . '_' . $id_usine;
        $cached = cache()->get($cacheKey);

        if (!$cached || empty($cached['results'])) {
            return redirect()
                ->route('verification-paie.usine', ['id_usine' => $id_usine])
                ->with('flash_error', 'Aucune donnée de vérification trouvée. Veuillez relancer la vérification.');
        }

        [$usine, $error] = $this->fetchUsineFromApi($id_usine);

        if ($error !== null) {
            return redirect()
                ->route('verification-paie.usine', ['id_usine' => $id_usine])
                ->with('flash_error', $error);
        }

        // Filtrer les tickets trouvés dans l'API
        $results = $cached['results'];
        $tickets = [];
        $poidsTotal = 0;

        foreach ($results as $r) {
            if (($r['statut'] ?? '') === 'trouve_api') {
                $poids = isset($r['poids']) ? (float) $r['poids'] : 0;
                $tickets[] = [
                    'numero' => $r['numero'] ?? '—',
                    'dateTicket' => isset($r['date_ticket']) ? Carbon::parse($r['date_ticket'])->format('d/m/Y') : '—',
                    'poids' => $poids > 0 ? number_format($poids, 0, ',', ' ') . ' kg' : '—',
                ];
                $poidsTotal += $poids;
            }
        }

        $nomUsine = $usine['nom_usine'] ?? ('Usine #' . $id_usine);

        return Pdf::loadView('verification-paie.trouves_pdf', [
            'tickets' => $tickets,
            'nomUsine' => $nomUsine,
            'idUsine' => (int) $id_usine,
            'generatedAt' => Carbon::now()->format('d/m/Y H:i'),
            'userName' => $request->user()->name ?: $request->user()->email,
            'poidsTotal' => $poidsTotal,
        ])
            ->setPaper('a4', 'portrait')
            ->stream('tickets-trouves-usine-' . $id_usine . '.pdf');
    }

    /**
     * @return array{usines: list<array<string, mixed>>, pagination: ?array<string, mixed>, error: ?string, search: string, ticketCountsByUsine: array<int, int>}
     */
    private function mesUsinesListContext(Request $request): array
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

        return [
            'usines' => $usines,
            'pagination' => $pagination,
            'error' => $error,
            'search' => $search,
            'ticketCountsByUsine' => VerifiedTicketCounts::byUsineForUser((int) $request->user()->id, $usineIds),
        ];
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
