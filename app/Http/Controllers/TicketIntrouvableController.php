<?php

namespace App\Http\Controllers;

use App\Models\TicketIntrouvable;
use App\Services\PegasusMesTicketsLookup;
use App\Services\PegasusReferenceLookup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketIntrouvableController extends Controller
{
    public function index(Request $request, PegasusReferenceLookup $lookup): View
    {
        $search = trim((string) $request->input('search', ''));
        $usineFilter = trim((string) $request->input('usine', ''));

        $query = TicketIntrouvable::query()
            ->with('utilisateur')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($search !== '') {
            $query->where('numero_ticket', 'like', '%'.$search.'%');
        }

        if ($usineFilter !== '' && ctype_digit($usineFilter)) {
            $query->where('id_usine', (int) $usineFilter);
        }

        $total = (clone $query)->count();
        $tickets = $query->paginate(20)->withQueryString();
        $usineNames = $lookup->usinesById();

        // Statistiques globales des tickets introuvables
        $stats = [
            'total' => TicketIntrouvable::count(),
            'today' => TicketIntrouvable::whereDate('created_at', today())->count(),
            'usines' => TicketIntrouvable::distinct('id_usine')->count('id_usine'),
        ];

        return view('tickets-introuvables.index', [
            'tickets' => $tickets,
            'usineNames' => $usineNames,
            'total' => $total,
            'search' => $search,
            'usineFilter' => $usineFilter,
            'stats' => $stats,
        ]);
    }

    public function reverify(Request $request, int $id, PegasusMesTicketsLookup $lookup): RedirectResponse
    {
        $ticket = TicketIntrouvable::findOrFail($id);
        $numero = $ticket->numero_ticket;
        $idUsine = $ticket->id_usine;

        // Chercher le ticket directement dans l'API
        $result = $lookup->findTicket($numero, $idUsine);

        if ($result['status'] === 'api_error') {
            return redirect()
                ->route('tickets-introuvables.index')
                ->with('flash_error', $result['message'] ?? 'Impossible de contacter l\'API pour vérifier le ticket.');
        }

        if ($result['status'] === 'found') {
            // Ticket trouvé ! On le supprime de la liste des introuvables
            $ticket->delete();

            return redirect()
                ->route('tickets-introuvables.index')
                ->with('flash_success', "Le ticket {$numero} a été retrouvé dans la base générale et supprimé de la liste.");
        }

        // Ticket toujours introuvable, mettre à jour la date de vérification
        $ticket->touch();

        return redirect()
            ->route('tickets-introuvables.index')
            ->with('flash_warning', "Le ticket {$numero} est toujours introuvable dans la base générale.");
    }
}
