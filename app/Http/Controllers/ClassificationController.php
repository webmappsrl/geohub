<?php

namespace App\Http\Controllers;

use App\Models\App;
use Exception;
use Illuminate\Support\Facades\Log;

class ClassificationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getRankedUsersNearPois(int $id)
    {
        try {
            $app = App::where('id', $id)->first();
            // Controlla se $app ha un app_id
            if (! isset($app->app_id)) {
                throw new Exception('App ID non trovato.');
            }

            // Controlla se $app->classification_show è false
            if ($app->classification_show === false) {
                throw new Exception('La classificazione non è mostrata.');
            }

            // Controlla se $app->getRankedUsersNearPois() è vuoto
            if (empty($app->getRankedUsersNearPois())) {
                throw new Exception('Nessun utente classificato.');
            }

            $classification = $app->getRankedUsersNearPois();
            $data = $app->getAllRankedUsersNearPoisData();

            return $this->beautifyRankedUsersNearPois($classification, $data);

        } catch (Exception $e) {
            // Log l'errore
            Log::error($e->getMessage());

            // Reindirizza alla pagina 404
            abort(404);
        }
    }

    /**
     * Beautify the ranked users near pois.
     *
     * @return array
     */
    public function beautifyRankedUsersNearPois(array $classification, $data)
    {
        $classificaTrasformata = [];
        foreach ($classification as $userId => $ecPoiArray) {
            $utente = isset($data['Users'][$userId]) ? $data['Users'][$userId] : null;

            if ($utente) {
                $dettagliUtente = [
                    'name' => isset($utente['name']) ? $utente['name'] : '',
                    'lastname' => isset($utente['last_name']) ? $utente['last_name'] : '',
                    'email' => isset($utente['email']) ? $utente['email'] : '',
                    'total_points' => 0, // Assumendo che tu possa calcolarlo
                    'pois' => [],
                ];

                foreach ($ecPoiArray as $ecPoiInfo) {
                    foreach ($ecPoiInfo as $ecPoiId => $ecMediaIds) {
                        $ecPoi = isset($data['EcPois'][$ecPoiId]) ? $data['EcPois'][$ecPoiId] : null;

                        if ($ecPoi) {
                            $dettagliPoi = [
                                'name' => isset($ecPoi['name']) ? $ecPoi['name'] : '',
                            ];

                            $idsMedia = explode(',', $ecMediaIds);
                            foreach ($idsMedia as $idMedia) {
                                $media = isset($data['UgcMedia'][$idMedia]) ? $data['UgcMedia'][$idMedia] : null;
                                if ($media) {
                                    $dettagliPoi['medias'][] = [
                                        'id' => $idMedia,
                                        'url' => isset($media['url']) ? $media['url'] : '',
                                    ];
                                }
                            }

                            $dettagliUtente['pois'][] = $dettagliPoi;
                            $dettagliUtente['total_points'] += 1;
                        }
                    }
                }

                $classificaTrasformata[] = $dettagliUtente;
            }
        }

        usort($classificaTrasformata, function ($a, $b) {
            return $b['total_points'] - $a['total_points'];
        });

        return $classificaTrasformata;
    }
}
