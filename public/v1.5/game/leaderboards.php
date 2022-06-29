<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

$gameID = requestInputSanitized('ID', null, 'integer');
if ($gameID == null || $gameID == 0) {
    header("Location: " . getenv('APP_URL') . "?e=urlissue");
    exit;
}

authenticateFromCookie($user, $permissions, $userDetails);

$gameData = getGameData($gameID);
$numLeaderboards = getLeaderboardsForGame($gameID, $leaderboardData, $user);

RenderHtmlStart(true);

?>
<head prefix="og: http://ogp.me/ns# retroachievements: http://ogp.me/ns/apps/retroachievements#">
    <?php RenderSharedHeader(); ?>
    <?php RenderTitleTag($gameData['Title'] . ' (' . $gameData['ConsoleName'] . ')'); ?>
</head>
<body>
<?php RenderHeader($userDetails); ?>
<div id="mainpage">
    <div id="fullcontainer">
        <?php RenderGameHeader($gameData, 'Leaderboards'); ?>
        <div style='margin-top: 4px'>
            <?php
                if ($numLeaderboards == 0) {
                    echo "No leaderboards found: why not suggest some for this game? ";
                    echo "<div class='rightalign'><a href='/leaderboardList.php'>Leaderboard List</a></div>";
                } else {
                    echo "<table><tbody>";

                    $count = 0;
                    foreach ($leaderboardData as $lbItem) {
                        if ($lbItem['DisplayOrder'] < 0) {
                            continue;
                        }

                        $lbID = $lbItem['LeaderboardID'];
                        $lbTitle = $lbItem['Title'];
                        $lbDesc = $lbItem['Description'];
                        $bestScoreUser = $lbItem['User'];
                        $bestScore = $lbItem['Score'];
                        $scoreFormat = $lbItem['Format'];

                        sanitize_outputs($lbTitle, $lbDesc);

                        // Title
                        echo "<tr>";
                        echo "<td colspan='2'>";
                        echo "<div class='fixheightcellsmaller'><a href='/leaderboardinfo.php?i=$lbID'>$lbTitle</a></div>";
                        echo "<div class='fixheightcellsmaller'>$lbDesc</div>";
                        echo "</td>";
                        echo "</tr>";

                        // Score/Best entry
                        echo "<tr class='altdark'>";
                        echo "<td>";
                        echo GetUserAndTooltipDiv($bestScoreUser, true);
                        echo GetUserAndTooltipDiv($bestScoreUser);
                        echo "</td>";
                        echo "<td>";
                        echo "<a href='/leaderboardinfo.php?i=$lbID'>";
                        if ($bestScoreUser == '') {
                            echo "No entries";
                        } else {
                            echo GetFormattedLeaderboardEntry($scoreFormat, $bestScore);
                        }
                        echo "</a>";
                        echo "</td>";
                        echo "</tr>";

                        $count++;
                    }

                    echo "</tbody></table>";
                }
            ?>
        </div>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
