<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PegasusReferenceLookup
{
    private const CACHE_TTL_SECONDS = 600;

    private const MAX_USINE_PAGES = 50;

    private const MAX_AGENT_PAGES = 40;

    /**
     * @return array<int, string> id_usine => nom_usine
     */
    public function usinesById(): array
    {
        return Cache::remember('pegasus_ref_usines_by_id_v1', self::CACHE_TTL_SECONDS, function (): array {
            $url = config('services.pegasus.mes_usines_url');
            $map = [];
            $page = 1;
            $lastPage = 1;

            try {
                do {
                    $response = Http::timeout(20)->acceptJson()->get($url, ['page' => $page]);
                    if (! $response->successful()) {
                        break;
                    }
                    $data = $response->json();
                    foreach (is_array($data['usines'] ?? null) ? $data['usines'] : [] as $u) {
                        $id = (int) ($u['id_usine'] ?? 0);
                        if ($id > 0) {
                            $map[$id] = (string) ($u['nom_usine'] ?? '');
                        }
                    }
                    $lastPage = max(1, (int) ($data['pagination']['last_page'] ?? 1));
                    $page++;
                } while ($page <= $lastPage && $page <= self::MAX_USINE_PAGES);
            } catch (\Throwable) {
                //
            }

            return $map;
        });
    }

    /**
     * @return array<int, string> id_agent => nom affichable
     */
    public function agentsById(): array
    {
        return Cache::remember('pegasus_ref_agents_by_id_v1', self::CACHE_TTL_SECONDS, function (): array {
            $url = config('services.pegasus.agents_url');
            $map = [];
            $page = 1;
            $lastPage = 1;

            try {
                do {
                    $response = Http::timeout(25)->acceptJson()->get($url, ['page' => $page]);
                    if (! $response->successful()) {
                        break;
                    }
                    $data = $response->json();
                    foreach (is_array($data['agents'] ?? null) ? $data['agents'] : [] as $a) {
                        $id = (int) ($a['id_agent'] ?? 0);
                        if ($id > 0) {
                            $nom = trim((string) ($a['nom_complet'] ?? ''));
                            if ($nom === '') {
                                $nom = trim((string) (($a['nom'] ?? '').' '.($a['prenom'] ?? '')));
                            }
                            $map[$id] = $nom !== '' ? $nom : ('#'.$id);
                        }
                    }
                    $lastPage = max(1, (int) ($data['pagination']['last_page'] ?? 1));
                    $page++;
                } while ($page <= $lastPage && $page <= self::MAX_AGENT_PAGES);
            } catch (\Throwable) {
                //
            }

            return $map;
        });
    }
}
