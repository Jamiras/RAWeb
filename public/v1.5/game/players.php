<?php

use RA\AchievementType;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

$gameID = requestInputSanitized('ID', null, 'integer');
if ($gameID == null || $gameID == 0) {
    header("Location: " . getenv('APP_URL') . "?e=urlissue");
    exit;
}

$friendScores = [];
if (authenticateFromCookie($user, $permissions, $userDetails)) {
    getAllFriendsProgress($user, $gameID, $friendScores);
}

$numAchievements = getGameMetaData($gameID, $user, $achievementData, $gameData);

$recentPlayerData = getGameRecentPlayers($gameID, 10);
$gameTopAchievers = getGameTopAchievers($gameID, $user);
$achDist = getAchievementDistribution($gameID, 0, $user, AchievementType::OfficialCore, $numAchievements); // for now, only retrieve casual!

RenderHtmlStart(true);

?>
<head prefix="og: http://ogp.me/ns# retroachievements: http://ogp.me/ns/apps/retroachievements#">
    <?php RenderSharedHeader(); ?>
    <?php RenderTitleTag($gameData['Title'] . ' (' . $gameData['ConsoleName'] . ')'); ?>
</head>
<body>
<?php RenderHeader($userDetails); ?>
<script src="https://www.gstatic.com/charts/loader.js"></script>
<script>
google.load('visualization', '1.0', {'packages': ['corechart']});
google.setOnLoadCallback(drawCharts);

function drawCharts() {
    var dataTotalScore = new google.visualization.DataTable();

    // Declare columns
    dataTotalScore.addColumn('number', 'Total Achievements Won');
    dataTotalScore.addColumn('number', 'Num Users');

    dataTotalScore.addRows([
        <?php
        $largestWonByCount = 0;
        $count = 0;
        for ($i = 1; $i <= $numAchievements; $i++) {
            if ($count++ > 0) {
                echo ", ";
            }
            $wonByUserCount = $achDist[$i];

            if ($wonByUserCount > $largestWonByCount) {
                $largestWonByCount = $wonByUserCount;
            }

            echo "[ {v:$i, f:\"Earned $i achievement(s)\"}, $wonByUserCount ] ";
        }

        if ($largestWonByCount > 30) {
            $largestWonByCount = -2;
        }
        ?>
    ]);
    var optionsTotalScore = {
        backgroundColor: 'transparent',
        titleTextStyle: {color: '#186DEE'}, // cc9900
        hAxis: {textStyle: {color: '#186DEE'}, gridlines: {count:<?= $numAchievements ?>, color: '#334433'}, minorGridlines: {count: 0}, format: '#', slantedTextAngle: 90, maxAlternation: 0},
        vAxis: {textStyle: {color: '#186DEE'}, gridlines: {count:<?= $largestWonByCount + 1 ?>}, viewWindow: {min: 0}, format: '#'},
        legend: {position: 'none'},
        chartArea: {'width': '85%', 'height': '78%'},
        height: 260,
        width: 600,
        colors: ['#cc9900'],
        pointSize: 4,
    };

    function resize() {
        chartScoreProgress = new google.visualization.AreaChart(document.getElementById('chart_distribution'));
        chartScoreProgress.draw(dataTotalScore, optionsTotalScore);
        // google.visualization.events.addListener(chartScoreProgress, 'select', selectHandlerScoreProgress );
    }

    window.onload = resize();
    window.onresize = resize;
}
</script>
<div id="mainpage">
    <div id="fullcontainer">
        <?php RenderGameHeader($gameData, 'Players'); ?>
        <div style='clear:both; margin-top: 4px'>
            <?php
                if (!empty($recentPlayerData)) {
                    RenderRecentGamePlayers($recentPlayerData);
                }
            ?>
        </div>
        <div style='margin-top: 10px'>
            <div id='latestmasters' style='float:left'>
                <h4>Recent Masteries</h4>
                <table class='smalltable'><tbody>
                    <tr><th>Pos</th><th colspan='2' style='max-width:30%'>User</th><th>Mastery Date</th></tr>
                    <?php
                        $i = 1;
                        foreach ($gameTopAchievers['Masters'] as $entry) {
                            // Outline user if they are in the list
                            if ($user !== null && $user == $entry['User']) {
                                echo "<tr style='outline: thin solid'>";
                            } else {
                                echo "<tr>";
                            }

                            echo "<td class='rank'>";
                            echo $i;
                            echo "</td>";

                            echo "<td>";
                            echo GetUserAndTooltipDiv($entry['User'], true);
                            echo "</td>";

                            echo "<td class='user'>";
                            echo GetUserAndTooltipDiv($entry['User'], false);
                            echo "</td>";

                            echo "<td>" . $entry['LastAward'] . "</td>";

                            echo "</tr>";

                            $i++;
                        }
                    ?>
                </tbody></table>
            </div>

            <div id='highscores' style='float:left; margin-left:20px'>
                <h4>High Scores</h4>
                <table class='smalltable'><tbody>
                    <tr><th>Pos</th><th colspan='2' style='max-width:30%'>User</th><th>Points</th></tr>
                    <?php
                        $i = 1;
                        foreach ($gameTopAchievers['HighScores'] as $entry) {
                            // Outline user if they are in the list
                            if ($user !== null && $user == $entry['User']) {
                                echo "<tr style='outline: thin solid'>";
                            } else {
                                echo "<tr>";
                            }

                            echo "<td class='rank'>";
                            echo $i;
                            echo "</td>";

                            echo "<td>";
                            echo GetUserAndTooltipDiv($entry['User'], true);
                            echo "</td>";

                            echo "<td class='user'>";
                            echo GetUserAndTooltipDiv($entry['User'], false);
                            echo "</td>";

                            $nextLastAward = $entry['LastAward'];
                            $nextPoints = $entry['TotalScore'];
                            echo "<td class='points'>";
                            echo "<span class='hoverable' title='Latest awarded at $nextLastAward'>$nextPoints</span>";
                            echo "</td>";

                            echo "</tr>";

                            $i++;
                        }
                    ?>
                </tbody></table>
            </div>

            <div id='friendscores' style='float:left; margin-left:20px'>
                <h4>Friend Scores</h4>
                <table class='smalltable'><tbody>
                    <tr><th>Pos</th><th colspan='2' style='max-width:30%'>User</th><th>Points</th></tr>
                    <?php
                        $i = 1;
                        foreach ($friendScores as $name => $entry) {
                            // Outline user if they are in the list
                            if ($user !== null && $user == $name) {
                                echo "<tr style='outline: thin solid'>";
                            } else {
                                echo "<tr>";
                            }

                            echo "<td class='rank'>";
                            echo $i;
                            echo "</td>";

                            echo "<td>";
                            echo GetUserAndTooltipDiv($name, true);
                            echo "</td>";

                            echo "<td class='user'>";
                            echo GetUserAndTooltipDiv($name, false);
                            echo "</td>";

                            echo "<td class='points'>" . $entry['TotalPoints'] . "</td>";

                            echo "</tr>";

                            $i++;
                            if ($i == 11) {
                                break;
                            }
                        }
                    ?>
                </tbody></table>
            </div>
        </div>

        <div style='clear:both'></div>

        <div style='margin-top: 10px'>
            <div id='achdistribution' class='component' style='float:left'>
                <h4>Achievement Distribution</h4>
                <div id='chart_distribution'></div>
            </div>

            <div id='compareuser' style='float:left; margin-left:20px'>
                <h4>Compare with User</h4>
                <form method='get' action='/gamecompare.php'>
                <input type='hidden' name='ID' value='$gameID'>
                <input size='24' name='f' type='text' class='searchboxgamecompareuser' placeholder='Enter User...' />
                <input type='submit' value='Select' />
                </form>
            </div>
        </div>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
