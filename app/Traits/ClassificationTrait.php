<?php

namespace App\Traits;

use App\Models\EcPoi;
use App\Models\UgcMedia;
use App\Models\User;
use Illuminate\Support\Facades\DB;

trait ClassificationTrait
{
    public function getRankedUsersNearPois()
    {
        $rankings = $this->getRankedUsersNearPoisQuery();

        $groupedArray = [];
        foreach ($rankings as $item) {
            $userId = $item['ugc_user_id'];

            // Initialize the user_id array if not already set
            if (!isset($groupedArray[$userId])) {
                $groupedArray[$userId] = [];
            }

            // Append the id:media_ids pair to the user_id key
            $groupedArray[$userId][] = $item;
        }
        uasort($groupedArray, function ($a, $b) {
            return count($b) - count($a); // Descending order
        });
        $groupedArray = array_slice($groupedArray, 0, 10, true);
        return $groupedArray;
    }

    public function getAllRankedUsersNearPoisData()
    {
        $rankings = $this->getRankedUsersNearPoisQuery();

        // Array where we will store the results
        $transformedArray = [
            'User' => [],
            'UgcMedia' => [],
            'EcPoi' => []
        ];

        // Loop through the original array to extract and organize the data
        foreach ($rankings as $element) {
            // Add the user_id if it is not already present
            if (!in_array($element['user_id'], $transformedArray['User'])) {
                $transformedArray['User'][] = $element['user_id'];
            }

            // Split the media_ids and add them if they are not already present
            $mediaIds = explode(',', $element['media_ids']);
            foreach ($mediaIds as $mediaId) {
                if (!in_array($mediaId, $transformedArray['UgcMedia'])) {
                    $transformedArray['UgcMedia'][] = $mediaId;
                }
            }

            // Add the id (EcPoi) if it is not already present
            if (!in_array($element['id'], $transformedArray['EcPoi'])) {
                $transformedArray['EcPoi'][] = $element['id'];
            }
        }

        // Query per ottenere tutti gli utenti (User) per gli ID specificati
        $users = User::whereIn('id', $transformedArray['User'])->get(['id', 'name', 'last_name', 'email']);
        $formattedusers = $users->reduce(function ($carry, $item) {
            $carry[$item->id] = [
                'name' => $item->name,
                'last_name' => $item->last_name,
                'email' => $item->email
            ];
            return $carry;
        }, []);

        // Query per ottenere tutti i media (UgcMedia) per gli ID specificati
        $ugcMedias = UgcMedia::whereIn('id', $transformedArray['UgcMedia'])->get(['id', 'relative_url']);
        $formattedugcMedias = $ugcMedias->reduce(function ($carry, $item) {
            $carry[$item->id] = [
                'url' => 'https://geohub.webmapp.it/storage/' . $item->relative_url
            ];
            return $carry;
        }, []);

        // Query per ottenere tutti i punti di interesse (EcPoi) per gli ID specificati
        $ecPois = EcPoi::whereIn('id', $transformedArray['EcPoi'])->get(['id', 'name']);
        $formattedEcPois = $ecPois->reduce(function ($carry, $item) {
            $carry[$item->id] = [
                'name' => $item->name
            ];
            return $carry;
        }, []);

        $data = [
            'Users' => $formattedusers,
            'UgcMedia' => $formattedugcMedias,
            'EcPois' => $formattedEcPois
        ];
        return $data;
    }

    public function getRankedUsersNearPoisQuery($ugcUserId = null)
    {
        $query = EcPoi::query()
            ->select(
                'ugc_media.user_id as ugc_user_id',
                DB::raw('string_agg(DISTINCT CAST(ugc_media.id as TEXT), \',\') as media_ids'),
                DB::raw('COUNT(DISTINCT ugc_media.user_id) as unique_media_count'),
                'ec_pois.*' // Seleziona tutti i campi di ec_pois
            )
            ->join('ugc_media', function ($join) {
                $join->on('ec_pois.user_id', '=', DB::raw("'" . $this->user_id . "'"))
                    ->whereRaw("ST_DWithin(ugc_media.geometry, ec_pois.geometry, 100.0)")
                    ->where('ugc_media.app_id', '=', DB::raw("'" . $this->app_id . "'"));
            });

        if ($ugcUserId) {
            $query->where('ugc_media.user_id', '=', $ugcUserId);
        }

        $result = $query
            ->groupBy('ugc_media.user_id', 'ec_pois.id')
            ->orderByDesc('unique_media_count')
            ->get()
            ->map(function ($item) {
                return [
                    'ugc_user_id' => $item->ugc_user_id,
                    'media_ids' => $item->media_ids,
                    'unique_media_count' => $item->unique_media_count,
                    'ec_poi' => [
                        'id' => $item->id,
                        'name' => $item->name,
                    ]
                ];
            });
        return $result;
    }

    public function getRankedUserPositionNearPoisQuery($ugcUserId)
    {
        // Ottieni la classifica completa degli utenti
        $rankings = $this->getRankedUsersNearPois();

        $userIds = array_keys($rankings);
        $position = array_search($ugcUserId, $userIds);

        if ($position !== false) {
            // Aggiungi 1 per ottenere la posizione reale (non zero-indicizzata)
            return $position + 1;
        } else {
            // Ritorna null se l'utente non è nella classifica
            return null;
        }
    }
}
