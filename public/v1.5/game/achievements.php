<?php

use RA\AchievementType;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

$gameID = requestInputSanitized('ID', null, 'integer');
if ($gameID == null || $gameID == 0) {
    header("Location: " . getenv('APP_URL') . "?e=urlissue");
    exit;
}

$flags = requestInputSanitized('f', AchievementType::OfficialCore, 'integer');
$sortBy = requestInputSanitized('s', 13, 'integer');

authenticateFromCookie($user, $permissions, $userDetails);

$numAchievements = getGameMetaData($gameID, $user, $achievementData, $gameData, $sortBy);
$numDistinctPlayersCasual = $gameData['NumDistinctPlayersCasual'];
$gameTitle = $gameData['Title'];

$totalEarnedCasual = 0;
$totalEarnedHardcore = 0;
$numEarnedCasual = 0;
$numEarnedHardcore = 0;
$totalPossible = 0;
$totalEarnedTrueRatio = 0;
$totalPossibleTrueRatio = 0;
$authorName = [];
$authorCount = [];
foreach ($achievementData as &$nextAch) {
    // Add author to array if it's not already there and initialize achievement count for that author.
    if (!in_array($nextAch['Author'], $authorName)) {
        $authorName[mb_strtolower($nextAch['Author'])] = $nextAch['Author'];
        $authorCount[mb_strtolower($nextAch['Author'])] = 1;
    } // If author is already in array then increment the achievement count for that author.
    else {
        $authorCount[mb_strtolower($nextAch['Author'])]++;
    }

    $totalPossible += $nextAch['Points'];
    $totalPossibleTrueRatio += $nextAch['TrueRatio'];

    if (isset($nextAch['DateEarned'])) {
        $numEarnedCasual++;
        $totalEarnedCasual += $nextAch['Points'];
        $totalEarnedTrueRatio += $nextAch['TrueRatio'];
    }
    if (isset($nextAch['DateEarnedHardcore'])) {
        $numEarnedHardcore++;
        $totalEarnedHardcore += $nextAch['Points'];
    }
}
// Combine arrays and sort by achievement count.
$authorInfo = array_combine($authorName, $authorCount);
array_multisort($authorCount, SORT_DESC, $authorInfo);

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
        <?php RenderGameHeader($gameData, 'Achievements'); ?>
        <div style='margin-top: 4px'>
            <?php
                if ($numAchievements > 0) {
                    echo "There are <b>$numAchievements</b> achievements worth <b>$totalPossible</b> <span class='TrueRatio'>($totalPossibleTrueRatio)</span> points.<br>";

                    echo "<b>Authors:</b> ";
                    $numItems = count($authorInfo);
                    $i = 0;
                    foreach ($authorInfo as $author => $achievementCount) {
                        echo GetUserAndTooltipDiv($author, false);
                        echo " (" . $achievementCount . ")";
                        if (++$i === $numItems) {
                            echo '.';
                        } else {
                            echo ', ';
                        }
                    }
                    echo "<br>";
                    echo "<br>";

                    if ($user !== null) {
                        $pctAwardedCasual = $numEarnedCasual / $numAchievements;
                        $pctAwardedHardcore = $numEarnedHardcore / $numAchievements;
                        $pctAwardedHardcoreProportion = 0;
                        if ($numEarnedHardcore > 0) {
                            $pctAwardedHardcoreProportion = $numEarnedHardcore / $numEarnedCasual;
                        }

                        $pctAwardedCasual = sprintf("%01.0f", $pctAwardedCasual * 100.0);
                        $pctAwardedHardcore = sprintf("%01.0f", $pctAwardedHardcoreProportion * 100.0);

                        $pctComplete = sprintf(
                            "%01.0f",
                            (($numEarnedCasual + $numEarnedHardcore) * 100.0 / $numAchievements)
                        );

                        echo "<div class='progressbar'>";
                        echo "<div class='completion' style='width:$pctAwardedCasual%'>";
                        echo "<div class='completionhardcore' style='width:$pctAwardedHardcore%'>&nbsp;</div>";
                        echo "</div>";
                        if ($pctComplete > 100.0) {
                            echo "<b>$pctComplete%</b> complete<br>";
                        } else {
                            echo "$pctComplete% complete<br>";
                        }
                        echo "</div>";

                        echo "<a href='/user/$user'>$user</a> has won <b>$numEarnedCasual</b> achievements";
                        if ($totalEarnedCasual > 0) {
                            echo ", worth <b>$totalEarnedCasual</b> <span class='TrueRatio'>($totalEarnedTrueRatio)</span> points";
                        }
                        echo ".<br>";
                        if ($numEarnedHardcore > 0) {
                            echo "<a href='/user/$user'>$user</a> has won <b>$numEarnedHardcore</b> HARDCORE achievements";
                            if ($totalEarnedHardcore > 0) {
                                echo ", worth a further <b>$totalEarnedHardcore</b> points";
                            }
                            echo ".<br>";
                        }
                    }

                    if ($numAchievements > 1) {
                        echo "<div class='sortbyselector'><span>";
                        echo "Sort: ";

                        $flagParam = ($flags != AchievementType::OfficialCore) ? "f=$flags" : '';

                        $sortType = ($sortBy < 10) ? "^" : "<sup>v</sup>";

                        $sort1 = ($sortBy == 1) ? 11 : 1;
                        $sort2 = ($sortBy == 2) ? 12 : 2;
                        $sort3 = ($sortBy == 3) ? 13 : 3;
                        $sort4 = ($sortBy == 4) ? 14 : 4;
                        $sort5 = ($sortBy == 5) ? 15 : 5;

                        $mark1 = ($sortBy % 10 == 1) ? "&nbsp;$sortType" : "";
                        $mark2 = ($sortBy % 10 == 2) ? "&nbsp;$sortType" : "";
                        $mark3 = ($sortBy % 10 == 3) ? "&nbsp;$sortType" : "";
                        $mark4 = ($sortBy % 10 == 4) ? "&nbsp;$sortType" : "";
                        $mark5 = ($sortBy % 10 == 5) ? "&nbsp;$sortType" : "";

                        echo "<a href='/v1.5/game/achievements.php?ID=$gameID?$flagParam&s=$sort1'>Normal$mark1</a> - ";
                        echo "<a href='/v1.5/game/achievements.php?ID=$gameID?$flagParam&s=$sort2'>Won By$mark2</a> - ";
                        echo "<a href='/v1.5/game/achievements.php?ID=$gameID?$flagParam&s=$sort4'>Points$mark4</a> - ";
                        echo "<a href='/v1.5/game/achievements.php?ID=$gameID?$flagParam&s=$sort5'>Title$mark5</a>";

                        echo "<sup>&nbsp;</sup></span></div>";
                    }

                    echo "<table class='achievementlist'><tbody>";

                    for ($i = 0; $i < 2; $i++) {
                        // $i = 0: earned achievements, $i = 1: unearned achievements
                        if ($i == 0 && $numEarnedCasual == 0) {
                            continue;
                        }

                        foreach ($achievementData as &$nextAch) {
                            $achieved = (isset($nextAch['DateEarned']));

                            if ($i == 0 && $achieved == false) {
                                continue;
                            }
                            if ($i == 1 && $achieved == true) {
                                continue;
                            }

                            $achID = $nextAch['ID'];
                            $achTitle = $nextAch['Title'];
                            $achDesc = $nextAch['Description'];
                            $achPoints = $nextAch['Points'];
                            $achTrueRatio = $nextAch['TrueRatio'];
                            $dateAch = "";
                            if ($achieved) {
                                $dateAch = $nextAch['DateEarned'];
                            }
                            $achBadgeName = $nextAch['BadgeName'];

                            sanitize_outputs(
                                $achTitle,
                                $achDesc,
                            );

                            $earnedOnHardcore = isset($nextAch['DateEarnedHardcore']);

                            $imgClass = $earnedOnHardcore ? 'goldimagebig' : 'badgeimg';
                            $tooltipText = $earnedOnHardcore ? '<br clear=all>Unlocked: ' . getNiceDate(strtotime($nextAch['DateEarnedHardcore'])) . '<br>-=HARDCORE=-' : '';

                            $wonBy = $nextAch['NumAwarded'];
                            $wonByHardcore = $nextAch['NumAwardedHardcore'];
                            if ($numDistinctPlayersCasual == 0) {
                                $completionPctCasual = "0";
                                $completionPctHardcore = "0";
                            } else {
                                $completionPctCasual = sprintf("%01.2f", ($wonBy / $numDistinctPlayersCasual) * 100);
                                $completionPctHardcore = sprintf("%01.2f", ($wonByHardcore / $numDistinctPlayersCasual) * 100);
                            }

                            if ($user == "" || !$achieved) {
                                $achBadgeName .= "_lock";
                            }

                            echo "<tr>";
                            echo "<td>";
                            echo "<div class='achievemententry'>";

                            echo "<div class='achievemententryicon'>";
                            echo GetAchievementAndTooltipDiv(
                                $achID,
                                $achTitle,
                                $achDesc,
                                $achPoints,
                                $gameTitle,
                                $achBadgeName,
                                true,
                                true,
                                $tooltipText,
                                64,
                                $imgClass
                            );
                            echo "</div>";

                            $pctAwardedCasual = 0;
                            $pctAwardedHardcore = 0;
                            $pctComplete = 0;

                            if ($numDistinctPlayersCasual) {
                                $pctAwardedCasual = $wonBy / $numDistinctPlayersCasual;
                                $pctAwardedHardcore = $wonByHardcore / $numDistinctPlayersCasual;
                                $pctAwardedHardcoreProportion = 0;
                                if ($wonByHardcore > 0 && $wonBy > 0) {
                                    $pctAwardedHardcoreProportion = $wonByHardcore / $wonBy;
                                }

                                $pctAwardedCasual = sprintf("%01.2f", $pctAwardedCasual * 100.0);
                                $pctAwardedHardcore = sprintf("%01.2f", $pctAwardedHardcoreProportion * 100.0);

                                $pctComplete = sprintf(
                                    "%01.2f",
                                    (($wonBy + $wonByHardcore) * 100.0 / $numDistinctPlayersCasual)
                                );
                            }

                            echo "<div class='progressbar allusers'>";
                            echo "<div class='completion allusers'             style='width:$pctAwardedCasual%'>";
                            echo "<div class='completionhardcore allusers'     style='width:$pctAwardedHardcore%'>";
                            echo "&nbsp;";
                            echo "</div>";
                            echo "</div>";
                            if ($wonByHardcore > 0) {
                                echo "won by $wonBy <strong alt='HARDCORE'>($wonByHardcore)</strong> of $numDistinctPlayersCasual ($pctAwardedCasual%)<br>";
                            } else {
                                echo "won by $wonBy of $numDistinctPlayersCasual ($pctAwardedCasual%)<br>";
                            }
                            echo "</div>"; // progressbar

                            echo "<div class='achievementdata'>";
                            echo GetAchievementAndTooltipDiv(
                                $achID,
                                $achTitle,
                                $achDesc,
                                $achPoints,
                                $gameTitle,
                                $achBadgeName,
                                false,
                                false,
                                "",
                                64,
                                $imgClass
                            );
                            echo " <span class='TrueRatio'>($achTrueRatio)</span>";
                            echo "<br>";
                            echo "$achDesc<br>";
                            echo "</div>";

                            if ($achieved) {
                                echo "<div class='date smalltext'>unlocked on<br>$dateAch<br></div>";
                            }
                            echo "</div>"; // achievemententry
                            echo "</td>";
                            echo "</tr>";
                        }
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
