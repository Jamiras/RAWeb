<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Community\Enums\AwardType;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\PlayerAchievementSet;
use App\Models\PlayerGame;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\AchievementSetType;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SummarizeGameStats extends Command
{
    protected $signature = 'ra:platform:game:summarize-stats {csvfile}';

    protected $description = 'Summarize stats dumped by dump-stats command';

    public function handle(): void
    {
        $csvfile = $this->argument('csvfile');
        $this->loadCsv($csvfile);

        $this->summarize();

        $count = 20;
        $this->mostPlayed($count);
        $this->mostPopular($count);
        $this->timeToMaster($count);
        $this->effortRequired($count);
        $this->difficultSets($count);

        $count = 10;
        $this->mostPlayedWithoutSets($count);
        $this->popularFirstGames($count);
        $this->popularFirstMasteries($count);
        $this->mostMasteredSets($count);

        if (Carbon::now()->month === 1 || Carbon::now()->month === 12) {
            $this->annualSummary($count);
        }
    }

    private function loadCsv(string $csvfile): void
    {
        if (($handle = fopen($csvfile, "r")) === false) {
            echo "error opening $csvfile\r\n";

            return;
        }

        $columns = [];
        foreach (fgetcsv($handle) as $index => $column) {
            $columns[$column] = $index;
        }

        $this->gameIdIndex = $columns['Id'];
        $this->titleIndex = $columns['Title'];
        $this->consoleIndex = $columns['ConsoleId'];
        $this->playersIndex = $columns['Players'];
        $this->hardcorePlayersIndex = $columns['PlayersWithHardcoreUnlocks'];
        $this->ageIndex = $columns['Age'];
        $this->pointsIndex = $columns['Points'];
        $this->minutesPerPointIndex = $columns['MinutesPerPoint'];
        $this->timesMasteredIndex = $columns['TimesMastered'];
        $this->meanTimeToMasterIndex = $columns['MeanTimeToMaster'];
        $this->stdDevTimeToMasterIndex = $columns['StdDevTimeToMaster'];
        $this->twentyFifthPercentilePointsIndex = $columns['TwentyFifthPercentilePoints'];
        $this->nintiethPercentilePointsIndex = $columns['NintiethPercentilePoints'];
        $this->playersPerDayColumnIndex = count($columns);
        $this->nintiethPercentileProgressIndex = count($columns) + 1;
        $this->twentyFifthPercentileProgressIndex = count($columns) + 2;

        $this->allGames = [];
        $this->allGameIds = [];
        $this->allSubsets = [];
        $this->allSubsetIds = [];
        while (($line = fgetcsv($handle)) !== false) {
            if ($line[$this->ageIndex] > 0) {
                $line[$this->playersPerDayColumnIndex] = $line[$this->playersIndex] / $line[$this->ageIndex];
            } else {
                $line[$this->playersPerDayColumnIndex] = $line[$this->playersIndex];
            }

            if ($line[$this->pointsIndex] > 0) {
                $line[$this->nintiethPercentileProgressIndex] = $line[$this->nintiethPercentilePointsIndex] / $line[$this->pointsIndex];
                $line[$this->twentyFifthPercentileProgressIndex] = $line[$this->twentyFifthPercentilePointsIndex] / $line[$this->pointsIndex];
            } else {
                $line[$this->nintiethPercentileProgressIndex] = 0;
                $line[$this->twentyFifthPercentileProgressIndex] = 0;
            }

            if (str_contains($line[$this->titleIndex], '[Subset')) {
                $this->allSubsets[] = $line;
                $this->allSubsetIds[] = $line[$this->gameIdIndex];
            } else {
                $this->allGames[] = $line;
                $this->allGameIds[] = $line[$this->gameIdIndex];
            }
        }

        $this->numGamesWithAchievements = count($this->allGames);

        $this->consoles = [];
        foreach (System::all() as $system) {
            $this->consoles[$system->id] = $system->name;
        }
    }

    public function formatTitle(array $row): void
    {
        if (empty($row)) {
            echo "null";
        } else {
            $consoleId = $row[$this->consoleIndex] ?? 0;
            $title = $row[$this->titleIndex] ?? '';
        }
        echo $title . ' (' . $this->consoles[$consoleId] . ')';
    }

    public function findGameData(int $id): array
    {
        foreach ($this->allGames as $gameData) {
            if ($gameData[$this->gameIdIndex] == $id) {
                return $gameData;
            }
        }

        $game = Game::find($id);
        if ($game) {
            return [
                $this->gameIdIndex => $game->ID,
                $this->titleIndex => $game->Title,
                $this->consoleIndex => $game->ConsoleID,
            ];
        }

        return null;
    }

    public function summarize(): void
    {
        $numSubsets = count($this->allSubsets);
        $numGames = Game::whereNotIn('ConsoleId', System::getNonGameSystems())->count() - $numSubsets;

        $numAchievements = Game::whereIn('ID', $this->allGameIds)->sum('achievements_published');

        $numSubsetAchievements = Game::whereIn('ID', $this->allSubsetIds)->sum('achievements_published');

        $numLeaderboards = DB::table('LeaderboardDef')->count();
        $numGamesWithLeaderboards = DB::table('LeaderboardDef')->distinct()->count('GameID');
        $numRichPresences = Game::whereRaw('LENGTH(RichPresencePatch) > 1')->count();
        $numDynamicRichPresences = Game::where('RichPresencePatch', 'LIKE', '%@%')->count();
        $numStaticRichPresences = $numRichPresences - $numDynamicRichPresences;
        $numAuthors = Achievement::published()->distinct('user_id')->count('user_id');
        $numSystems = System::whereNotIn('ID', System::getNonGameSystems())->count();

        echo "Games:         " . str_pad((string) $numGames, 6, ' ', STR_PAD_LEFT) . "\r\n";
        echo "Achievements:  " . str_pad((string) $numAchievements, 6, ' ', STR_PAD_LEFT);
        echo " ($this->numGamesWithAchievements games with achievements)\r\n";
        echo "Leaderboards:  " . str_pad((string) $numLeaderboards, 6, ' ', STR_PAD_LEFT);
        echo " ($numGamesWithLeaderboards games with leaderboards)\r\n";
        echo "RichPresences: " . str_pad((string) $numRichPresences, 6, ' ', STR_PAD_LEFT);
        echo " ($numStaticRichPresences static)\r\n";
        echo "Subsets:       " . str_pad((string) $numSubsets, 6, ' ', STR_PAD_LEFT) . "\r\n";
        echo "Subset Achs:   " . str_pad((string) $numSubsetAchievements, 6, ' ', STR_PAD_LEFT) . "\r\n";
        echo "Authors:       " . str_pad((string) $numAuthors, 6, ' ', STR_PAD_LEFT) . "\r\n";
        echo "Systems:       " . str_pad((string) $numSystems, 6, ' ', STR_PAD_LEFT) . "\r\n";
        echo "\r\n";
    }

    public function mostPlayed(int $count): void
    {
        // ==================================
        echo "Most played: MAX(Players)\r\n```\r\n";
        usort($this->allGames, function ($a, $b) {
            $diff = $b[$this->playersIndex] - $a[$this->playersIndex];
            if ($diff == 0) {
                $diff = $a[$this->ageIndex] - $b[$this->ageIndex];
            }

            return $diff; // Players DESC, Age ASC
        });

        $j = 0;
        for ($i = 0; $i < $this->numGamesWithAchievements; $i++) {
            echo str_pad($this->allGames[$i][$this->playersIndex], 6, ' ', STR_PAD_LEFT);
            echo " ";
            echo $this->formatTitle($this->allGames[$i]);
            echo "\r\n";

            $j++;
            if ($j > $count) {
                break;
            }
        }
        echo "```\r\n\r\n";

        // ==================================
        echo "Least played: MIN(Players)|Age [Players > 0, Age > 30 days]\r\n```\r\n";
        $i = $this->numGamesWithAchievements;
        $j = 0;
        $lastPlayerCount = 0;
        while ($i-- > 0) {
            if ($this->allGames[$i][$this->playersIndex] == 0) {
                continue;
            }
            if ($this->allGames[$i][$this->ageIndex] <= 30) {
                continue;
            }

            $j++;
            if ($j > $count && $this->allGames[$i][$this->playersIndex] > $lastPlayerCount) {
                break;
            }
            $lastPlayerCount = $this->allGames[$i][$this->playersIndex];

            echo str_pad($lastPlayerCount, 4, ' ', STR_PAD_LEFT);
            echo str_pad(number_format((float) $this->allGames[$i][$this->ageIndex], 4, '.', ''), 11, ' ', STR_PAD_LEFT);
            echo " ";
            echo $this->formatTitle($this->allGames[$i]);
            echo "\r\n";
        }
        echo "```\r\n\r\n";
    }

    public function mostPopular(int $count): void
    {
        echo "Most popular: MAX(Players/Day)|Players|Age [Age > 30 days]\r\n```\r\n";
        usort($this->allGames, fn ($a, $b) => ($b[$this->playersPerDayColumnIndex] * 1000000) - ($a[$this->playersPerDayColumnIndex] * 1000000));

        $j = 0;
        for ($i = 0; $i < $this->numGamesWithAchievements; $i++) {
            if ($this->allGames[$i][$this->ageIndex] <= 30) {
                continue;
            }
            echo str_pad(number_format($this->allGames[$i][$this->playersPerDayColumnIndex], 3), 7, ' ', STR_PAD_LEFT);
            echo str_pad($this->allGames[$i][$this->playersIndex], 7, ' ', STR_PAD_LEFT);
            echo str_pad(number_format((float) $this->allGames[$i][$this->ageIndex], 4, '.', ''), 11, ' ', STR_PAD_LEFT);
            echo " ";
            echo $this->formatTitle($this->allGames[$i]);
            echo "\r\n";

            $j++;
            if ($j > $count) {
                break;
            }
        }
        echo "```\r\n\r\n";

        // ==================================
        echo "Least popular: MIN(Players/Day)|Players|Age [Age > 30 days]\r\n```\r\n";
        $i = $this->numGamesWithAchievements;
        $j = 0;
        while ($i-- > 0) {
            if ($this->allGames[$i][$this->ageIndex] <= 30) {
                continue;
            }
            echo str_pad(number_format($this->allGames[$i][$this->playersPerDayColumnIndex], 3), 7, ' ', STR_PAD_LEFT);
            echo str_pad($this->allGames[$i][$this->playersIndex], 7, ' ', STR_PAD_LEFT);
            echo str_pad(number_format((float) $this->allGames[$i][$this->ageIndex], 4, '.', ''), 11, ' ', STR_PAD_LEFT);
            echo " ";
            echo $this->formatTitle($this->allGames[$i]);
            echo "\r\n";

            $j++;
            if ($j > $count) {
                break;
            }
        }
        echo "```\r\n\r\n";
    }

    public function timeToMaster(int $count): void
    {
        echo "Slowest to master: MAX(MeanTimeToMaster)|StdDevTimeToMaster|TimesMastered/Players|Points [TimesMastered >= 3]\r\n```\r\n";
        usort($this->allGames, fn ($a, $b) => ($b[$this->meanTimeToMasterIndex] * 1000) - ($a[$this->meanTimeToMasterIndex] * 1000));

        $j = 0;
        for ($i = 0; $i < $this->numGamesWithAchievements; $i++) {
            if ($this->allGames[$i][$this->timesMasteredIndex] < 3) {
                continue;
            }
            echo str_pad(number_format((float) $this->allGames[$i][$this->meanTimeToMasterIndex], 2, '.', ''), 9, ' ', STR_PAD_LEFT);
            echo str_pad(number_format((float) $this->allGames[$i][$this->stdDevTimeToMasterIndex], 2, '.', ''), 10, ' ', STR_PAD_LEFT);
            echo str_pad($this->allGames[$i][$this->timesMasteredIndex], 5, ' ', STR_PAD_LEFT);
            echo '/';
            echo str_pad($this->allGames[$i][$this->playersIndex], 5, ' ', STR_PAD_LEFT);
            echo str_pad($this->allGames[$i][$this->pointsIndex], 6, ' ', STR_PAD_LEFT);
            echo ' ';
            echo $this->formatTitle($this->allGames[$i]);
            echo "\r\n";

            $j++;
            if ($j > $count) {
                break;
            }
        }
        echo "```\r\n\r\n";

        // ==================================
        echo "Fastest to master: MIN(MeanTimeToMaster)|StdDevTimeToMaster|TimesMastered/Players|Points [TimesMastered >= 3, Points >= 50]\r\n```\r\n";
        $i = $this->numGamesWithAchievements;
        $j = 0;
        while ($i-- > 0) {
            if ($this->allGames[$i][$this->timesMasteredIndex] < 3) {
                continue;
            }
            if ($this->allGames[$i][$this->pointsIndex] < 50) {
                continue;
            }
            echo str_pad(number_format((float) $this->allGames[$i][$this->meanTimeToMasterIndex], 3, '.', ''), 8, ' ', STR_PAD_LEFT);
            echo str_pad(number_format((float) $this->allGames[$i][$this->stdDevTimeToMasterIndex], 3, '.', ''), 8, ' ', STR_PAD_LEFT);
            echo str_pad($this->allGames[$i][$this->timesMasteredIndex], 5, ' ', STR_PAD_LEFT);
            echo '/';
            echo str_pad($this->allGames[$i][$this->playersIndex], 5, ' ', STR_PAD_LEFT);
            echo str_pad($this->allGames[$i][$this->pointsIndex], 5, ' ', STR_PAD_LEFT);
            echo ' ';
            echo $this->formatTitle($this->allGames[$i]);
            echo "\r\n";

            $j++;
            if ($j > $count) {
                break;
            }
        }
        echo "```\r\n\r\n";

        // ==================================
        echo "Fastest to master: MIN(MeanTimeToMaster)|StdDevTimeToMaster|TimesMastered/Players|Points [TimesMastered >= 3, Points >= 400]\r\n```\r\n";
        $i = $this->numGamesWithAchievements;
        $j = 0;
        while ($i-- > 0) {
            if ($this->allGames[$i][$this->timesMasteredIndex] < 3) {
                continue;
            }
            if ($this->allGames[$i][$this->pointsIndex] < 400) {
                continue;
            }
            echo str_pad(number_format((float) $this->allGames[$i][$this->meanTimeToMasterIndex], 3, '.', ''), 9, ' ', STR_PAD_LEFT);
            echo str_pad(number_format((float) $this->allGames[$i][$this->stdDevTimeToMasterIndex], 3, '.', ''), 9, ' ', STR_PAD_LEFT);
            echo str_pad($this->allGames[$i][$this->timesMasteredIndex], 5, ' ', STR_PAD_LEFT);
            echo '/';
            echo str_pad($this->allGames[$i][$this->playersIndex], 5, ' ', STR_PAD_LEFT);
            echo str_pad($this->allGames[$i][$this->pointsIndex], 5, ' ', STR_PAD_LEFT);
            echo ' ';
            echo $this->formatTitle($this->allGames[$i]);
            echo "\r\n";

            $j++;
            if ($j > $count) {
                break;
            }
        }
        echo "```\r\n\r\n";
    }

    private function effortRequired(int $count): void
    {
        echo "Points requiring the least effort: MIN(MinutesPerPoint)|NintiethPercentilePoints|Players [NintiethPercentilePoints >= 10, Players >= 3]\r\n```\r\n";
        usort($this->allGames, fn ($a, $b) => ($a[$this->minutesPerPointIndex] * 1000000) - ($b[$this->minutesPerPointIndex] * 1000000));

        $j = 0;
        for ($i = 0; $i < $this->numGamesWithAchievements; $i++) {
            if ($this->allGames[$i][$this->playersIndex] < 3) {
                continue;
            }
            if ($this->allGames[$i][$this->nintiethPercentilePointsIndex] < 10) {
                continue;
            }
            echo str_pad(number_format((float) $this->allGames[$i][$this->minutesPerPointIndex], 3, '.', ''), 8, ' ', STR_PAD_LEFT);
            echo str_pad($this->allGames[$i][$this->nintiethPercentilePointsIndex], 5, ' ', STR_PAD_LEFT);
            echo str_pad($this->allGames[$i][$this->playersIndex], 6, ' ', STR_PAD_LEFT);
            echo ' ';
            echo $this->formatTitle($this->allGames[$i]);
            echo "\r\n";

            $j++;
            if ($j > $count) {
                break;
            }
        }
        echo "```\r\n\r\n";

        // ==================================
        echo "Points requiring the most effort: MAX(MinutesPerPoint)|NintiethPercentilePoints|Players [NintiethPercentilePoints >= 10, Players >= 3]\r\n```\r\n";
        $i = $this->numGamesWithAchievements;
        $j = 0;
        while ($i-- > 0) {
            if ($this->allGames[$i][$this->playersIndex] < 3) {
                continue;
            }
            if ($this->allGames[$i][$this->nintiethPercentilePointsIndex] < 10) {
                continue;
            }
            echo str_pad(number_format((float) $this->allGames[$i][$this->minutesPerPointIndex], 3, '.', ''), 8, ' ', STR_PAD_LEFT);
            echo str_pad($this->allGames[$i][$this->nintiethPercentilePointsIndex], 5, ' ', STR_PAD_LEFT);
            echo str_pad($this->allGames[$i][$this->playersIndex], 6, ' ', STR_PAD_LEFT);
            echo ' ';
            echo $this->formatTitle($this->allGames[$i]);
            echo "\r\n";

            $j++;
            if ($j > $count) {
                break;
            }
        }
        echo "```\r\n\r\n";
    }

    private function difficultSets(int $count): void
    {
        echo "Easiest sets: AverageHardcoreCompletion|NintiethPercentilePoints/Points|Players [Players >= 10, Points >= 50]\r\n```\r\n";
        usort($this->allGames, fn ($a, $b) => ($b[$this->nintiethPercentileProgressIndex] * 1000000) - ($a[$this->nintiethPercentileProgressIndex] * 1000000));

        $gameIds = [];

        $j = 0;
        $rate = 1.0;
        for ($i = 0; $i < $this->numGamesWithAchievements; $i++) {
            $game = $this->allGames[$i];
            if ($game[$this->playersIndex] < 10) {
                continue;
            }
            if ($game[$this->pointsIndex] < 50) {
                continue;
            }

            if ($j > $count && $rate !== $game[$this->nintiethPercentileProgressIndex]) {
                break;
            }

            $gameIds[] = $game[$this->gameIdIndex];

            $j++;
            if ($j > $count && $rate !== $game[$this->nintiethPercentileProgressIndex]) {
                break;
            }

            $rate = $game[$this->nintiethPercentileProgressIndex];
        }

        $j = 0;
        $rows = DB::select("SELECT game_id, avg(points_hardcore) / avg(points_total) as pct FROM player_games WHERE game_id IN (" . implode(',', $gameIds) . ") AND points_hardcore > 0 GROUP BY game_id ORDER BY pct DESC");
        foreach ($rows as $row) {
            $game = $this->findGameData((int) $row->game_id);

            echo str_pad(number_format((float) $row->pct * 100, 1, '.', ''), 6, ' ', STR_PAD_LEFT);
            echo '%';
            echo str_pad($game[$this->nintiethPercentilePointsIndex], 5, ' ', STR_PAD_LEFT);
            echo '/';
            echo str_pad($game[$this->pointsIndex], 4, ' ', STR_PAD_LEFT);
            echo str_pad($game[$this->playersIndex], 6, ' ', STR_PAD_LEFT);
            echo ' ';
            echo $this->formatTitle($game);
            echo "\r\n";

            $j++;
            if ($j > $count) {
                break;
            }
        }
        echo "```\r\n\r\n";

        // ==================================
        echo "Hardest sets: MIN(TwentyFifthPercentilePoints/Points)|TwentyFifthPercentilePoints/Points|Players [Players >= 10, TwentyFifthPercentilePoints >= 25]\r\n```\r\n";
        usort($this->allGames, fn ($a, $b) => ($a[$this->twentyFifthPercentileProgressIndex] * 1000000) - ($b[$this->twentyFifthPercentileProgressIndex] * 1000000));
        $j = 0;
        for ($i = 0; $i < $this->numGamesWithAchievements; $i++) {
            if ($this->allGames[$i][$this->playersIndex] < 10) {
                continue;
            }
            if ($this->allGames[$i][$this->twentyFifthPercentilePointsIndex] < 25) {
                continue;
            }
            echo str_pad(number_format((float) $this->allGames[$i][$this->twentyFifthPercentileProgressIndex] * 100, 1, '.', ''), 6, ' ', STR_PAD_LEFT);
            echo '%';
            echo str_pad($this->allGames[$i][$this->twentyFifthPercentilePointsIndex], 4, ' ', STR_PAD_LEFT);
            echo '/';
            echo str_pad($this->allGames[$i][$this->pointsIndex], 4, ' ', STR_PAD_LEFT);
            echo str_pad($this->allGames[$i][$this->playersIndex], 6, ' ', STR_PAD_LEFT);
            echo ' ';
            echo $this->formatTitle($this->allGames[$i]);
            echo "\r\n";

            $j++;
            if ($j > $count) {
                break;
            }
        }
        echo "```\r\n\r\n";
    }

    private function popularFirstGames(int $count): void
    {
        echo "Popular first games: [AccountAge < 1 month, PlayTime >= 5 minutes]\r\n```\r\n";

        $firstGames = [];
        $numFirstGames = 0;

        $threshold = Carbon::now()->subMonths(1);
        foreach (User::where('Created', '>=', $threshold)->pluck('ID') as $userId) {
            $query = PlayerAchievementSet::where('user_id', $userId)
                ->where('created_at', '>=', $threshold)
                ->where('time_taken', '>=', 5)
                ->orderBy('created_at')
                ->select('achievement_set_id');

            $firstGame = $query->first();
            if ($firstGame) {
                $firstGames[$firstGame->achievement_set_id] = ($firstGames[$firstGame->achievement_set_id] ?? 0) + 1;
                $numFirstGames++;
            }
        }

        asort($firstGames);
        $mostPlayed = array_reverse(array_slice($firstGames, -10, preserve_keys: true), preserve_keys: true);

        $j = 0;
        foreach ($mostPlayed as $id => $num) {
            $gameId = GameAchievementSet::where('achievement_set_id', $id)
                ->where('type', AchievementSetType::Core->value)
                ->select('game_id')
                ->pluck('game_id')
                ->first();
            $gameData = $this->findGameData($gameId);
            echo str_pad(number_format((float) $num / $numFirstGames * 100, 1, '.', ''), 6, ' ', STR_PAD_LEFT);
            echo '%';
            echo str_pad("{$num}", 6, ' ', STR_PAD_LEFT);
            echo " ";
            echo $this->formatTitle($gameData);
            echo "\r\n";

            $j++;
            if ($j === $count) {
                break;
            }
        }

        echo "```\r\n\r\n";
    }

    private function ignoreGame(array $game): bool
    {
        if ($game[$this->consoleIndex] === 100 // hubs
            || $game[$this->consoleIndex] === 101 // events
            || $game[$this->consoleIndex] === 102) { // standalone
            return true;
        }

        if (str_contains($game[$this->titleIndex], '[Subset -')) {
            return true;
        }

        return false;
    }

    private function popularFirstMasteries(int $count): void
    {
        echo "Popular first masteries: [AccountAge < 6 months]\r\n```\r\n";

        $query = PlayerGame::where('completed_hardcore_at', '>', Carbon::now()->subMonths(6))
            ->whereIn('user_id', function ($query) {
                $query->select('ID')->from('UserAccounts')->where('Created', '>', Carbon::now()->subMonths(6));
            })
            ->orderByDesc('completed_hardcore_at')
            ->select(['user_id', 'game_id']);

        $firstPlayerMasteries = [];
        foreach ($query->get() as $mastery) {
            $game = $this->findGameData($mastery->game_id);
            if ($this->ignoreGame($game)) {
                continue;
            }
            $firstPlayerMasteries[$mastery->user_id] = $mastery->game_id;
        }

        $firstMasteries = [];
        $numFirstMasteries = count($firstPlayerMasteries);

        foreach ($firstPlayerMasteries as $game_id) {
            $firstMasteries[$game_id] = ($firstMasteries[$game_id] ?? 0) + 1;
        }

        asort($firstMasteries);
        $mostPlayed = array_reverse(array_slice($firstMasteries, -10, preserve_keys: true), preserve_keys: true);

        $j = 0;
        foreach ($mostPlayed as $id => $num) {
            $gameData = $this->findGameData($id);
            echo str_pad(number_format((float) $num / $numFirstMasteries * 100, 1, '.', ''), 6, ' ', STR_PAD_LEFT);
            echo '%';
            echo str_pad("{$num}", 6, ' ', STR_PAD_LEFT);
            echo " ";
            echo $this->formatTitle($gameData);
            echo "\r\n";

            $j++;
            if ($j === $count) {
                break;
            }
        }

        echo "```\r\n\r\n";
    }

    private function mostAwards(int $type, int $extra, ?Carbon $after, int $count): void
    {
        $query = "SELECT a.AwardData AS game_id, count(*) AS num
                  FROM SiteAwards a
                  JOIN GameData g ON g.ID=a.AwardData
                  WHERE a.AwardType=$type
                  AND a.AwardDataExtra=$extra
                  AND g.ConsoleID NOT IN (100, 101)
                  AND g.Title NOT LIKE '%[Subset - %' ";

        if ($after !== null) {
            $query .= "AND a.AwardDate >= '" . $after->format('Y-m-d') . "' ";
        }

        $query .= "GROUP BY AwardData
                   ORDER BY num DESC
                   LIMIT $count";

        foreach (DB::select($query) as $row) {
            $gameData = $this->findGameData($row->game_id);
            echo str_pad("{$row->num}", 6, ' ', STR_PAD_LEFT);
            echo " ";
            echo $this->formatTitle($gameData);
            echo "\r\n";
        }
        echo "```\r\n\r\n";
    }

    private function mostMasteredSets(int $count): void
    {
        echo "Most Mastered Sets: MAX(TimesMastered)\r\n```\r\n";
        $this->mostAwards(1, 1, null, $count);
    }

    private function mostPlayedWithoutSets(int $count): void
    {
        echo "Most Played Games without achievements: MAX(Players) [PlayTime >= 5 minutes in last 30 days]\r\n```\r\n";

        $query = "SELECT game_id, COUNT(DISTINCT user_id) as num
                    FROM player_sessions
                    WHERE rich_presence_updated_at >= '" . Carbon::now()->clone()->subMonths(1)->format('Y-m-d') . "'
                    AND duration >= 5
                    AND game_id IN (
                        SELECT ID FROM GameData
                        WHERE achievements_published=0 AND achievements_unpublished<6
                        AND ConsoleID NOT IN (100,101)
                    )
                    GROUP BY 1 ORDER BY 2 DESC LIMIT $count";

        foreach (DB::select($query) as $row) {
            $gameData = $this->findGameData($row->game_id);
            echo str_pad("{$row->num}", 6, ' ', STR_PAD_LEFT);
            echo " ";
            echo $this->formatTitle($gameData);
            echo "\r\n";
        }
        echo "```\r\n\r\n";
    }

    private function annualSummary(int $count): void
    {
        $year = Carbon::now()->subMonths(2)->year;
        $startOfYear = Carbon::create($year, 1, 1);

        echo "Most Mastered Sets in $year: MAX(TimesMastered), Date >= $year-01-01\r\n```\r\n";
        $this->mostAwards(AwardType::Mastery, 1, $startOfYear, $count);

        echo "Most Beaten(hardcore) Sets in $year: MAX(TimesBeatenHardcore), Date >= $year-01-01\r\n```\r\n";
        $this->mostAwards(AwardType::GameBeaten, 1, $startOfYear, $count);

        echo "Most Completed Sets in $year: MAX(TimesCompleted), Date >= $year-01-01\r\n```\r\n";
        $this->mostAwards(AwardType::Mastery, 0, $startOfYear, $count);

        echo "Most Beaten(softcore) Sets in $year: MAX(TimesBeaten), Date >= $year-01-01\r\n```\r\n";
        $this->mostAwards(AwardType::GameBeaten, 0, $startOfYear, $count);

        $doubleCount = $count * 2;

        $query = "SELECT MIN(id) AS min_id FROM player_achievements WHERE unlocked_at >= '$year-01-01'";
        $minId = 0;
        foreach (DB::select($query) as $row) {
            $minId = $row->min_id;
            break;
        }

        echo "Most Earned Achievement (hardcore) in $year: MAX(UnlockCount), Date >= $year-01-01\r\n```\r\n";
        $query = "SELECT achievement_id, count(*) AS num
                  FROM player_achievements
                  WHERE id >= $minId
                  GROUP BY achievement_id
                  ORDER BY num DESC
                  LIMIT $doubleCount";

        $j = 0;
        foreach (DB::select($query) as $row) {
            $achievement = Achievement::find($row->achievement_id);
            if (!$achievement) {
                continue;
            }
            $gameData = $this->findGameData($achievement->GameId);
            if ($this->ignoreGame($gameData)) {
                continue;
            }
            echo str_pad("{$row->num}", 6, ' ', STR_PAD_LEFT);
            echo " ";
            echo $achievement->Title;
            echo " from ";
            echo $this->formatTitle($gameData);
            echo "\r\n";

            $j++;
            if ($j === $count) {
                break;
            }
        }
        echo "```\r\n\r\n";

        echo "Most Earned Achievement (softcore) in $year: MAX(SoftcoreUnlockCount), Date >= $year-01-01\r\n```\r\n";
        $query = "SELECT achievement_id, count(*) AS num
                  FROM player_achievements
                  WHERE id >= $minId
                  AND unlocked_hardcore_at IS NULL
                  GROUP BY achievement_id
                  ORDER BY num DESC
                  LIMIT $doubleCount";

        $j = 0;
        foreach (DB::select($query) as $row) {
            $achievement = Achievement::find($row->achievement_id);
            if (!$achievement) {
                continue;
            }
            $gameData = $this->findGameData($achievement->GameId);
            if ($this->ignoreGame($gameData)) {
                continue;
            }
            echo str_pad("{$row->num}", 6, ' ', STR_PAD_LEFT);
            echo " ";
            echo $achievement->Title;
            echo " from ";
            echo $this->formatTitle($gameData);
            echo "\r\n";

            $j++;
            if ($j === $count) {
                break;
            }
        }
        echo "```\r\n\r\n";

        echo "Most Time Spent in Game in $year: MAX(TotalMinutes)|PlayerCount, Date >= $year-01-01\r\n```\r\n";

        $query = "SELECT MIN(id) AS min_id FROM player_sessions WHERE created_at >= '$year-01-01'";
        $minId = 0;
        foreach (DB::select($query) as $row) {
            $minId = $row->min_id;
            break;
        }

        $query = "SELECT game_id, SUM(duration) AS mins
                  FROM player_sessions
                  WHERE id >= $minId
                  AND duration >= 5
                  GROUP BY game_id HAVING mins > 10
                  ORDER BY mins DESC
                  LIMIT 100";

        $j = 0;
        $stats = [];
        $game_ids = [];
        foreach (DB::select($query) as $row) {
            $gameData = $this->findGameData($row->game_id);
            if ($this->ignoreGame($gameData)) {
                continue;
            }

            $game_ids[] = $row->game_id;
            $stats[] = $row;

            $j++;
            if ($j === $count) {
                break;
            }
        }

        $query = "SELECT game_id, COUNT(distinct user_id) AS num
                  FROM player_sessions
                  WHERE id >= $minId
                  AND duration >= 5
                  AND game_id IN (" . implode(',', $game_ids) . ")
                  GROUP BY game_id";
        $players = [];
        foreach (DB::select($query) as $row) {
            $players[$row->game_id] = $row->num;
        }

        foreach ($stats as $row) {
            $gameData = $this->findGameData($row->game_id);
            echo str_pad("{$row->mins}", 10, ' ', STR_PAD_LEFT);
            echo " ";
            $num = $players[$row->game_id] ?? 0;
            echo str_pad("{$num}", 6, ' ', STR_PAD_LEFT);
            echo " ";
            echo $this->formatTitle($gameData);
            echo "\r\n";
        }
        echo "```\r\n\r\n";
    }
}
