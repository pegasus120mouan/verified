<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Services\PegasusReferenceLookup;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(Request $request, PegasusReferenceLookup $lookup): View
    {
        $baseQuery = Ticket::query()->where('id_utilisateur', $request->user()->id);

        if ($u = trim((string) $request->input('usine', ''))) {
            if (ctype_digit($u)) {
                $baseQuery->where('id_usine', (int) $u);
            }
        }

        if ($a = trim((string) $request->input('agent', ''))) {
            if (ctype_digit($a)) {
                $baseQuery->where('id_agent', (int) $a);
            }
        }

        $tickets = (clone $baseQuery)
            ->orderByDesc('date_ticket')
            ->orderByDesc('id_ticket')
            ->paginate(20)
            ->withQueryString();

        $usineNames = $lookup->usinesById();
        $agentNames = $lookup->agentsById();

        $agentsPrintOptions = collect($agentNames)
            ->map(fn (string $nom, int|string $id) => ['id' => (int) $id, 'nom' => $nom])
            ->sortBy('nom', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();

        return view('tickets.index', compact('tickets', 'usineNames', 'agentNames', 'agentsPrintOptions'));
    }

    public function print(Request $request, PegasusReferenceLookup $lookup): Response|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date'],
            'id_agent' => ['nullable', 'integer', 'min:1'],
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
                ->route('tickets.index')
                ->withErrors($validator)
                ->withInput()
                ->with('open_print_modal', true);
        }

        $query = Ticket::query()
            ->where('id_utilisateur', $request->user()->id)
            ->orderByDesc('date_ticket')
            ->orderByDesc('id_ticket');

        $idAgent = (int) $request->input('id_agent', 0);
        if ($idAgent > 0) {
            $query->where('id_agent', $idAgent);
        }

        if ($request->filled('date_debut')) {
            $query->where('created_at', '>=', Carbon::parse($request->date('date_debut'))->startOfDay());
        }

        if ($request->filled('date_fin')) {
            $query->where('created_at', '<=', Carbon::parse($request->date('date_fin'))->endOfDay());
        }

        $tickets = $query->get();
        $usineNames = $lookup->usinesById();
        $agentNames = $lookup->agentsById();

        $filterAgentLabel = null;
        if ($idAgent > 0) {
            $filterAgentLabel = $agentNames[$idAgent] ?? ('Agent #'.$idAgent);
        }

        $filterDateDebut = $request->filled('date_debut')
            ? Carbon::parse($request->date('date_debut'))->format('d/m/Y')
            : null;
        $filterDateFin = $request->filled('date_fin')
            ? Carbon::parse($request->date('date_fin'))->format('d/m/Y')
            : null;

        return Pdf::loadView('tickets.print_pdf', [
            'tickets' => $tickets,
            'usineNames' => $usineNames,
            'agentNames' => $agentNames,
            'filterAgentLabel' => $filterAgentLabel,
            'filterDateDebut' => $filterDateDebut,
            'filterDateFin' => $filterDateFin,
            'verifierName' => $request->user()->name ?: $request->user()->email,
        ])
            ->setPaper('a4', 'portrait')
            ->stream('mes-tickets.pdf');
    }

    public function show(Request $request, Ticket $ticket, PegasusReferenceLookup $lookup): View
    {
        abort_unless((int) $ticket->id_utilisateur === (int) $request->user()->id, 403);

        $usineNames = $lookup->usinesById();
        $agentNames = $lookup->agentsById();

        return view('tickets.show', compact('ticket', 'usineNames', 'agentNames'));
    }
}
