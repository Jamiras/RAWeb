<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

$gameID = requestInputSanitized('ID', null, 'integer');
if ($gameID == null || $gameID == 0) {
    header("Location: " . getenv('APP_URL') . "?e=urlissue");
    exit;
}

authenticateFromCookie($user, $permissions, $userDetails);

$numAchievements = getGameMetaData($gameID, $user, $achievementData, $gameData);
$numLeaderboards = getLeaderboardsForGame($gameID, $leaderboardData, $user);

$numPoints = 0;
foreach ($achievementData as $achievement) {
    $numPoints += $achievement['Points'];
}

$screenshotWidth = 200;
$screenshotMaxHeight = 240; // corresponds to the DS screen aspect ratio

$relatedGames = getGameAlternatives($gameID);
$gameAlts = [];
$gameHubs = [];
$gameSubsets = [];
$subsetPrefix = $gameData['Title'] . " [Subset - ";
foreach ($relatedGames as $gameAlt) {
    if ($gameAlt['ConsoleName'] == 'Hubs') {
        $gameHubs[] = $gameAlt;
    } else {
        if (str_starts_with($gameAlt['Title'], $subsetPrefix)) {
            $gameSubsets[] = $gameAlt;
        } else {
            $gameAlts[] = $gameAlt;
        }
    }
}

RenderHtmlStart(true);

?>
<head prefix="og: http://ogp.me/ns# retroachievements: http://ogp.me/ns/apps/retroachievements#">
    <?php RenderSharedHeader(); ?>
    <?php RenderTitleTag($gameData['Title'] . ' (' . $gameData['ConsoleName'] .')'); ?>
</head>
<body>
<?php RenderHeader($userDetails); ?>
<div id="mainpage">
    <div id="fullcontainer">
        <?php RenderGameHeader($gameData, 'Overview'); ?>
        <div style='margin-top: 4px'>
            <div style='float: right'>
                <img src='<?= $gameData['ImageBoxArt'] ?>' style='max-width: 256px'/>
            </div>
            <div style='overflow: hidden'>
            <?php
            RenderPageStatistic('Achievements', $numAchievements);
            RenderPageStatistic('Points', $numPoints);
            RenderPageStatistic('Leaderboards', $numLeaderboards);
            RenderPageStatistic('Players', $gameData['NumDistinctPlayersCasual']);
            ?>
            </div>
            <div style='margin-top:10px; overflow: hidden'>
                <table class='gameinfo'><tbody>
                <tr><td>System:</td><td><b><a href='/gameList.php?c=<?= $gameData['ConsoleID'] ?>'><?= $gameData['ConsoleName'] ?></a></b></td></tr>
                <?php
                RenderMetadataTableRow('Developer', $gameData['Developer'] ?? null, $gameHubs, ['Hacker']);
                RenderMetadataTableRow('Publisher', $gameData['Publisher'] ?? null, $gameHubs, ['Hacks']);
                RenderMetadataTableRow('Genre', $gameData['Genre'] ?? null, $gameHubs, ['Subgenre']);
                RenderMetadataTableRow('Released', $gameData['Released'] ?? null);
                ?>
                </tbody></table>
            </div>
            <div class='gamescreenshots' style='margin-top: 20px; margin-right:266px; width=100%'>
                <div style='float:left; width:50%'>
                    <img src='<?= $gameData['ImageTitle'] ?>' style='max-width:<?= $screenshotWidth ?>px;max-height:<?= $screenshotMaxHeight ?>px;margin-left:auto;margin-right:auto;display:block' alt='Title Screenshot'>
                </div>
                <div style='float:left; width:50%'>
                    <img src='<?= $gameData['ImageIngame'] ?>' style='max-width:<?= $screenshotWidth ?>px;max-height:<?= $screenshotMaxHeight ?>px;margin-left:auto;margin-right:auto;display:block' alt='In-game Screenshot'>
                </div>
            </div>
        </div>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
