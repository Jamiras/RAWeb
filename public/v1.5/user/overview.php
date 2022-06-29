<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

$userPage = requestInputSanitized('ID');
if ($userPage == null || mb_strlen($userPage) == 0 || ctype_alnum($userPage) == false) {
    header("Location: " . getenv('APP_URL') . "?e=urlissue");
    exit;
}

authenticateFromCookie($user, $permissions, $userDetails);

getUserPageInfo($userPage, $userData, 0, 0, $user);
if (!$userData) {
    http_response_code(404);
    echo "user not found";
    exit;
}

$retroRatio = "n/a";
if ($userData['TotalPoints'] > 0) {
    $retroRatio = sprintf("%01.2f", $userData['TotalTruePoints'] / $userData['TotalPoints']);
}

$averageCompletion = "n/a";
$totalCompletion = 0;
$gamesPlayed = 0;
$userCompletedGamesList = getUsersCompletedGamesAndMax($userPage);
foreach ($userCompletedGamesList as $nextGame) {
    if ($nextGame['PctWon'] > 0) {
        if ($nextGame['ConsoleID'] < 100) { // exclude Hubs and Events
            $totalCompletion += $nextGame['PctWon'];
            $gamesPlayed++;
        }
    }
}
if ($gamesPlayed > 0) {
    $averageCompletion = sprintf("%01.2f%%", $totalCompletion * 100 / $gamesPlayed);
}

$niceDateJoined = $userData['MemberSince'] ? getNiceDate(strtotime($userData['MemberSince'])) : null;
$niceDateLastActivity = $userData['LastActivity'] ? getNiceDate(strtotime($userData['LastActivity'])) : null;

$siteRank = null;
if ($userData['Untracked']) {
    $siteRank = "<b>Untracked</b>";
} elseif ($userData['TotalPoints'] < MIN_POINTS) {
    $siteRank = "<i>Needs at least " . MIN_POINTS . " points.</i>";
} else {
    $userRank = $userData['Rank'];
    $countRankedUsers = countRankedUsers();
    $rankPct = sprintf("%1.2f", (($userRank / $countRankedUsers) * 100.0));
    $rankOffset = (int) (($userRank - 1) / 25) * 25;
    $siteRank = "<a href='/globalRanking.php?s=5&t=2&o=$rankOffset'>$userRank</a> / $countRankedUsers ranked users (Top $rankPct%)";
}

RenderHtmlStart(true);

?>
<head prefix="og: http://ogp.me/ns# retroachievements: http://ogp.me/ns/apps/retroachievements#">
    <?php RenderSharedHeader(); ?>
    <?php RenderTitleTag($userPage); ?>
</head>
<body>
<?php RenderHeader($userDetails); ?>
<div id="mainpage">
    <div id="fullcontainer">
        <?php RenderUserHeader($userData, 'Overview', $userPage == $user); ?>
        <div style='margin-top: 4px'>
            <div style='overflow: hidden'>
            <?php
            RenderPageStatistic('Points', (string) $userData['TotalPoints']);
            RenderPageStatistic('Retro Points', (string) $userData['TotalTruePoints']);
            RenderPageStatistic('Retro Ratio', $retroRatio);
            RenderPageStatistic('Games Played', (string) $gamesPlayed);
            RenderPageStatistic('Average Completion', $averageCompletion);
            ?>
            </div>
            <div style='margin-top:10px; margin-right:266px; overflow: hidden'>
                <table class='gameinfo'><tbody>
                <?php
                RenderMetadataTableRow('Site Rank', $siteRank);
                RenderMetadataTableRow('Member Since', $niceDateJoined);
                RenderMetadataTableRow('Last Activity', $niceDateLastActivity);
                ?>
                </tbody></table>
            </div>
            <?php if (!empty($userData['RichPresenceMsg']) && $userData['RichPresenceMsg'] !== 'Unknown') { ?>
                <div class='gamescreenshots' style='margin-top: 20px; margin-right:266px; width=100%'>
                    <?php
                    echo "<div class='mottocontainer'>Last seen ";
                    if (!empty($userData['LastGameID'])) {
                        $game = getGameData($userData['LastGameID']);
                        echo ' in ' . GetGameAndTooltipDiv($game['ID'], $game['Title'], $game['ImageIcon'], null, false, 22) . '<br>';
                    }
                    echo "<code>" . $userData['RichPresenceMsg'] . "</code></div>";
                    ?>
                </div>
            <?php } ?>
        </div>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
