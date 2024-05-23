<?php

namespace App\Http\Controllers;

use App\Models\App;
use Illuminate\Http\Request;

class RankingController extends Controller
{
    public function showTopTen($appid)
    {
        // Supponiamo che l'ID dell'app sia 1
        if (!is_null($appid)) {

            $app = App::find($appid);
            $topTen = $app->getRankedUsersNearPois();

            return view('top-ten', ['topTen' => $topTen, 'app' => $app]);
        }
    }

    public function showUserRanking($appId, $userId)
    {
        $app = App::findOrFail($appId);
        $rankings = $app->getRankedUsersNearPois();
        $userIds = array_keys($rankings);

        // Find the position of the user
        $position = array_search($userId, $userIds);



        // Get the three users before and three users after
        $start = max(0, $position - 3);
        $end = min(count($userIds) - 1, $position + 3);
        $subset = array_slice($rankings, $start, $end - $start + 1, true);

        return view('user-ranking', [
            'rankings' => $subset,
            'position' => $position + 1, // Convert to 1-based index
            'userId' => $userId,
            'app' => $app
        ]);
    }
}
