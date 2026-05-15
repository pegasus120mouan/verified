<?php

namespace App\Http\Controllers;

use App\Models\TicketIntrouvable;
use App\Services\PegasusReferenceLookup;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketIntrouvableController extends Controller
{
    public function index(Request $request, PegasusReferenceLookup $lookup): View
    {
        $search = trim((string) $request->input('search', ''));
        $usineFilter = trim((string) $request->input('usine', ''));

        $query = TicketIntrouvable::query()
            ->where('id_utilisateur', $request->user()->id)
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

        return view('tickets-introuvables.index', [
            'tickets' => $tickets,
            'usineNames' => $usineNames,
            'total' => $total,
            'search' => $search,
            'usineFilter' => $usineFilter,
        ]);
    }
}
