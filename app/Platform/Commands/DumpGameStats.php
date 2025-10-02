<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\Game;
use App\Models\PlayerAchievementSet;
use App\Platform\Enums\AchievementSetType;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DumpGameStats extends Command
{
    // $ time sail artisan ra:platform:player:update-estimated-times
    //   - takes about a minute
    // $ time sail artisan ra:platform:game:dump-stats | tee ~/results.csv
    //   - takes about 10 minutes
    // $ cp ~/results.csv ~/source/RAWeb/storage/logs/results.csv
    // $ time sail artisan ra:platform:game:summarize-stats /var/www/html/storage/logs/results.csv | tee ~/mastery.txt
    //   - takes about a minute

    protected $signature = 'ra:platform:game:dump-stats {gameId?}';

    protected $description = 'Dumps stats for a game';

    public function handle(): void
    {
        $gameId = $this->argument('gameId');

        if ($gameId !== null) {
            $games = Game::where('id', $gameId);
        } else {
            $games = Game::where('achievements_published', '>', '0')
                         ->where('ConsoleID', '<', '100');
        }

        echo "Id,Title,ConsoleId,Created,Age,Points,Players,PlayersWithHardcoreUnlocks," .
              "TimesMastered,MedianTimeToMaster,MeanTimeToMaster,StdDevTimeToMaster,MinutesPerPointToMaster," .
              "MinutesPerPoint,AverageCompletion,MinutesPerPointHardcore,AverageCompletionHardcore," .
              "TwentyFifthPercentilePoints,FiftiethPercentilePoints,SeventyFifthPercentilePoints,NintiethPercentilePoints\r\n";

        $count = $games->count();

        $games->chunkById(100, function ($games) {
            foreach ($games as $game) {
                $this->dumpGame($game);
            }
        });
    }

    private function dumpGame(Game $game): void
    {
        $set = $game->achievementSets()->wherePivot('type', '=', AchievementSetType::Core->value)->first();
        $setCreated = $set->achievements_first_published_at;
        $numPlayers = $set->players_total;
        $numHardcorePlayers = $set->players_hardcore;
        $numAchievements = $set->achievements_published;
        $points = $set->points_total;

        // generic information about anyone who has played the set
        $sums = PlayerAchievementSet::where('achievement_set_id', '=', $set->id)
            ->select([
                DB::raw('SUM(points) AS totalPoints'),
                DB::raw('SUM(' . ifStatement('points > 0', '1', '0') . ') AS withPoints'),
                DB::raw('SUM(points_hardcore) AS totalPointsHardcore'),
                DB::raw('SUM(' . ifStatement('points_hardcore > 0', '1', '0') . ') AS withPointsHardcore'),
                DB::raw('SUM(time_taken) AS totalAchievementTime'),
                DB::raw('SUM(' . ifStatement('points_hardcore > 0', 'time_taken', '0') . ') AS totalAchievementTimeHardcore'),
                DB::raw('SUM(achievements_unlocked) AS totalAchievementsUnlocked'),
                DB::raw('SUM(achievements_unlocked_hardcore) AS totalAchievementsUnlockedHardcore'),
            ])
            ->first();

        $totalPointsEarned = $sums->totalPoints;
        $pointCount = $sums->withPoints;
        $totalPointsEarnedHardcore = $sums->totalPointsHardcore;
        $pointCountHardcore = $sums->withPointsHardcore;
        $totalTimeSeconds = $sums->totalAchievementTime;
        $totalTimeSecondsHardcore = $sums->totalAchievementTimeHardcore;
        $totalAchievementsUnlocked = $sums->totalAchievementsUnlocked;
        $totalAchievementsUnlockedHardcore = $sums->totalAchievementsUnlockedHardcore;

        // mastery information
        if ($set->times_completed_hardcore === 0) {
            $totalMasteryTimeSeconds = 0;
            $masters = 0;
            $meanTimeToMaster = 0;
            $stdDevTimeToMaster = 0.0;
        } else {
            // calculate the standard deviation for mastery time
            $sums = PlayerAchievementSet::where('achievement_set_id', '=', $set->id)
                ->where('achievements_unlocked_hardcore', '=', $set->achievements_published)
                ->select([
                    DB::raw('SUM(time_taken_hardcore) AS totalMasteryTime'),
                    DB::raw('SUM(1) AS timesMastered'),
                    DB::raw('STD(time_taken_hardcore) AS stdDevMasteryTime'),
                ])
                ->first();
            $totalMasteryTimeSeconds = $sums->totalMasteryTime;
            $masters = $sums->timesMastered;
            $meanTimeToMaster = $masters > 0 ? ($totalMasteryTimeSeconds / $masters) : 0;
            $stdDevTimeToMaster = $sums->stdDevMasteryTime;

            // redo the query, filtering out outliers (3x std dev)
            $sums = PlayerAchievementSet::where('achievement_set_id', '=', $set->id)
                ->where('time_taken_hardcore', '>=', $meanTimeToMaster - $stdDevTimeToMaster * 3)
                ->where('time_taken_hardcore', '<=', $meanTimeToMaster + $stdDevTimeToMaster * 3)
                ->where('achievements_unlocked_hardcore', '=', $set->achievements_published)
                ->select([
                    DB::raw('SUM(time_taken_hardcore) AS totalMasteryTime'),
                    DB::raw('SUM(1) AS timesMastered'),
                    DB::raw('STD(time_taken_hardcore) AS stdDevMasteryTime'),
                ])
                ->first();
            $totalMasteryTimeSeconds = $sums->totalMasteryTime;
            $masters = $sums->timesMastered;
            $meanTimeToMaster = $masters > 0 ? ($totalMasteryTimeSeconds / $masters) : 0;
            $stdDevTimeToMaster = $sums->stdDevMasteryTime;
        }

        // ID,Title,ConsoleId,Created,Age,Points,Players,PlayersWithHardcoreUnlocks
        echo "{$game->ID},";
        echo '"' . str_replace('"', '\\"', $game->Title) . '",';
        echo "{$game->ConsoleID},";
        echo $setCreated->toDateTimeString() . ",";
        echo round(Carbon::now()->diffInDays(Carbon::parse($setCreated), true), 4) . ",";
        echo "{$set->points_total},";
        echo "{$set->players_total},";
        echo "{$set->players_hardcore},";

        // TimesMastered,MedianTimeToMaster,MeanTimeToMaster,StdDevTimeToMaster,MinutesPerPointToMaster
        echo "$masters,";
        echo round($set->median_time_to_complete_hardcore / 60, 4) . ",";
        echo round($meanTimeToMaster / 60, 4) . ",";
        echo round($stdDevTimeToMaster / 60, 4) . ",";
        echo round($set->points_total > 0 ? $meanTimeToMaster / $set->points_total / 60 : 0, 4) . ",";

        // MinutesPerPoint,AverageCompletion,
        if ($totalPointsEarned > 0) {
            echo round($totalTimeSeconds / $totalPointsEarned / 60, 4) . ",";
            if ($set->players_hardcore > 0) {
                echo round($totalAchievementsUnlocked / ($numAchievements * $numPlayers) * 100.0, 4) . ",";
            } else {
                echo "0,";
            }
        } else {
            echo "0,0,";
        }

        // MinutesPerPointHardcore,AverageCompletionHardcore,
        // TwentyFifthPercentilePoints,FiftiethPercentilePoints,SeventyFifthPercentilePoints,NintiethPercentilePoints
        if ($totalPointsEarnedHardcore > 0) {
            echo round($totalTimeSecondsHardcore / $totalPointsEarnedHardcore / 60, 4) . ",";
            if ($set->players_hardcore > 0) {
                echo round($totalAchievementsUnlocked / ($numAchievements * $numHardcorePlayers) * 100.0, 4) . ",";
            } else {
                echo "0,";
            }

            $playerGamesWithPoints = PlayerAchievementSet::where('achievement_set_id', '=', $set->id)
                ->where('achievements_unlocked_hardcore', '>', 0)
                ->orderBy('points_hardcore');

            $pct75 = $playerGamesWithPoints->offset($pointCountHardcore * 0.75)->select('points_hardcore')->limit(1)->first();
            echo $pct75->points_hardcore . ",";

            $pct50 = $playerGamesWithPoints->offset($pointCountHardcore * 0.50)->select('points_hardcore')->limit(1)->first();
            echo $pct50->points_hardcore . ",";

            $pct25 = $playerGamesWithPoints->offset($pointCountHardcore * 0.25)->select('points_hardcore')->limit(1)->first();
            echo $pct25->points_hardcore . ",";

            $pct10 = $playerGamesWithPoints->offset($pointCountHardcore * 0.10)->select('points_hardcore')->limit(1)->first();
            echo $pct10->points_hardcore;
        } else {
            echo "0,0,0,0,0,0";
        }

        echo "\r\n";
    }
}
