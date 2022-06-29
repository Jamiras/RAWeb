<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

$userPage = requestInputSanitized('ID');
if ($userPage == null || mb_strlen($userPage) == 0 || ctype_alnum($userPage) == false) {
    header("Location: " . getenv('APP_URL') . "?e=urlissue");
    exit;
}

authenticateFromCookie($user, $permissions, $userDetails);

$maxNumGamesToFetch = 5;
getUserPageInfo($userPage, $userData, $maxNumGamesToFetch, 0, $user);
if (!$userData) {
    http_response_code(404);
    echo "user not found";
    exit;
}

RenderHtmlStart(true);

?>
<head prefix="og: http://ogp.me/ns# retroachievements: http://ogp.me/ns/apps/retroachievements#">
    <?php RenderSharedHeader(); ?>
    <?php RenderTitleTag($userPage); ?>
</head>
<body>
<?php RenderHeader($userDetails); ?>
<script src="https://www.gstatic.com/charts/loader.js"></script>
<script>
  google.load('visualization', '1.0', { 'packages': ['corechart'] });
  google.setOnLoadCallback(drawCharts);

  function drawCharts() {
    var dataRecentProgress = new google.visualization.DataTable();

    // Declare columns
    dataRecentProgress.addColumn('date', 'Date');    // NOT date! this is non-continuous data
    dataRecentProgress.addColumn('number', 'Score');

    dataRecentProgress.addRows([
        <?php
        $daysRecentProgressToShow = 14; // fortnight
        $userScoreData = getAwardedList(
            $userPage,
            0,
            1000,
            date("Y-m-d H:i:s", time() - 60 * 60 * 24 * $daysRecentProgressToShow),
            date("Y-m-d H:i:s", time())
        );

        $count = 0;
        foreach ($userScoreData as $dayInfo) {
            if ($count++ > 0) {
                echo ", ";
            }

            $nextDay = (int) $dayInfo['Day'];
            $nextMonth = (int) $dayInfo['Month'] - 1;
            $nextYear = (int) $dayInfo['Year'];
            $nextDate = $dayInfo['Date'];

            $dateStr = getNiceDate(strtotime($nextDate), true);
            $value = $dayInfo['CumulScore'];

            echo "[ {v:new Date($nextYear,$nextMonth,$nextDay), f:'$dateStr'}, $value ]";
        }
        ?>
    ]);

    var optionsRecentProcess = {
      backgroundColor: 'transparent',
      title: 'Recent Progress',
      titleTextStyle: { color: '#186DEE' },
      hAxis: { textStyle: { color: '#186DEE' }, slantedTextAngle: 90 },
      vAxis: { textStyle: { color: '#186DEE' } },
      legend: { position: 'none' },
      chartArea: { left: 42, width: 458, 'height': '100%' },
      showRowNumber: false,
      view: { columns: [0, 1] },
      colors: ['#cc9900'],
    };

    function resize() {
      chartRecentProgress = new google.visualization.AreaChart(document.getElementById('chart_recentprogress'));
      chartRecentProgress.draw(dataRecentProgress, optionsRecentProcess);
    }

    window.onload = resize();
    window.onresize = resize;
  }
</script>
<div id="mainpage">
    <div id="fullcontainer">
        <?php RenderUserHeader($userData, 'Recent', $userPage == $user); ?>
        <div style='margin-top: 4px'>
            <div style='float: right; width: 300px'>
                <h4>Recent Progress</h4>
                <div id='chart_recentprogress'></div>
                <div class='rightalign'><a href='/history.php?u=$userPage'>more...</a></div>
            </div>
            <div style='margin-top:10px; margin-right:310px; overflow: hidden'>
                <?php
                $recentlyPlayedCount = $userData['RecentlyPlayedCount'];
                echo "<h4>Last $recentlyPlayedCount games played:</h4>";
                for ($i = 0; $i < $recentlyPlayedCount; $i++) {
                    $gameID = $userData['RecentlyPlayed'][$i]['GameID'];
                    $consoleID = $userData['RecentlyPlayed'][$i]['ConsoleID'];
                    $consoleName = $userData['RecentlyPlayed'][$i]['ConsoleName'];
                    $gameTitle = $userData['RecentlyPlayed'][$i]['Title'];
                    $gameLastPlayed = $userData['RecentlyPlayed'][$i]['LastPlayed'];
        
                    sanitize_outputs($consoleName, $gameTitle);
        
                    $pctAwarded = 100.0;
        
                    if (isset($userData['Awarded'][$gameID])) {
                        $numPossibleAchievements = $userData['Awarded'][$gameID]['NumPossibleAchievements'];
                        $maxPossibleScore = $userData['Awarded'][$gameID]['PossibleScore'];
                        $numAchieved = $userData['Awarded'][$gameID]['NumAchieved'];
                        $scoreEarned = $userData['Awarded'][$gameID]['ScoreAchieved'];
                        $numAchievedHardcore = $userData['Awarded'][$gameID]['NumAchievedHardcore'];
                        $scoreEarnedHardcore = $userData['Awarded'][$gameID]['ScoreAchievedHardcore'];
        
                        settype($numPossibleAchievements, "integer");
                        settype($maxPossibleScore, "integer");
                        settype($numAchieved, "integer");
                        settype($scoreEarned, "integer");
                        settype($numAchievedHardcore, "integer");
                        settype($scoreEarnedHardcore, "integer");
        
                        echo "<div class='userpagegames'>";
        
                        $pctAwardedCasual = "0";
                        $pctAwardedHardcore = "0";
                        $pctComplete = "0";
        
                        if ($numPossibleAchievements > 0) {
                            $pctAwardedCasualVal = $numAchieved / $numPossibleAchievements;
        
                            $pctAwardedHardcoreProportion = 0;
                            if ($numAchieved > 0) {
                                $pctAwardedHardcoreProportion = $numAchievedHardcore / $numAchieved;
                            }
        
                            $pctAwardedCasual = sprintf("%01.0f", $pctAwardedCasualVal * 100.0);
                            $pctAwardedHardcore = sprintf("%01.0f", $pctAwardedHardcoreProportion * 100.0);
                            $pctComplete = sprintf(
                                "%01.0f",
                                (($numAchieved + $numAchievedHardcore) * 100.0 / $numPossibleAchievements)
                            );
                        }
        
                        echo "<div class='progressbar'>";
                        echo "<div class='completion'             style='width:$pctAwardedCasual%'>";
                        echo "<div class='completionhardcore'     style='width:$pctAwardedHardcore%'>";
                        echo "&nbsp;";
                        echo "</div>";
                        echo "</div>";
                        if ($pctComplete > 100.0) {
                            echo "<b>$pctComplete%</b> complete<br>";
                        } else {
                            echo "$pctComplete% complete<br>";
                        }
                        echo "</div>";
        
                        echo "<a href='/game/$gameID'>$gameTitle ($consoleName)</a><br>";
                        echo "Last played $gameLastPlayed<br>";
                        echo "Earned $numAchieved of $numPossibleAchievements achievements, $scoreEarned/$maxPossibleScore points.<br>";
        
                        if (isset($userData['RecentAchievements'][$gameID])) {
                            foreach ($userData['RecentAchievements'][$gameID] as $achID => $achData) {
                                $badgeName = $achData['BadgeName'];
                                $achID = $achData['ID'];
                                $achPoints = $achData['Points'];
                                $achTitle = $achData['Title'];
                                $achDesc = $achData['Description'];
                                $achUnlockDate = getNiceDate(strtotime($achData['DateAwarded']));
                                $achHardcore = $achData['HardcoreAchieved'];
        
                                $unlockedStr = "";
                                $class = 'badgeimglarge';
        
                                if (!$achData['IsAwarded']) {
                                    $badgeName .= "_lock";
                                } else {
                                    $unlockedStr = "<br clear=all>Unlocked: $achUnlockDate";
                                    if ($achHardcore == 1) {
                                        $unlockedStr .= "<br>-=HARDCORE=-";
                                        $class = 'goldimage';
                                    }
                                }
        
                                echo GetAchievementAndTooltipDiv(
                                    $achID,
                                    $achTitle,
                                    $achDesc,
                                    $achPoints,
                                    $gameTitle,
                                    $badgeName,
                                    true,
                                    true,
                                    $unlockedStr,
                                    48,
                                    $class
                                );
                            }
                        }
        
                        echo "</div>";
                    }
        
                    echo "<br>";
                }
        
                /* this link does nothing...
                if ($maxNumGamesToFetch == 5 && $recentlyPlayedCount == 5) {
                    echo "<div class='rightalign'><a href='/user/$userPage?g=15'>more...</a></div><br>";
                }
                */
                ?>
            </div>
        </div>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
