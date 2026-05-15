<?php

namespace App\View\Composers;

use App\Services\TicketStatsService;
use Illuminate\View\View;

class TicketStatsComposer
{
    public function __construct(
        private readonly TicketStatsService $ticketStats,
    ) {}

    public function compose(View $view): void
    {
        if (request()->routeIs('agents.index')) {
            return;
        }

        $user = auth()->user();

        if ($user === null) {
            return;
        }

        $view->with('ticketStats', $this->ticketStats->forUser((int) $user->id));
    }
}
